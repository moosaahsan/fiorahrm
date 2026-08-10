<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Services\CompensatoryLeaveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Sweeps attendance for holiday work that has no compensatory leave credit yet.
 *
 * The AttendanceObserver already catches this as records are saved; this covers
 * backfills, records created before the feature existed, and the rare case where
 * a holiday was configured after the attendance was entered.
 */
class ScanCompensatoryLeave extends Command
{
    protected $signature = 'cpl:scan
                            {--from= : Start date (Y-m-d), defaults to the start of this month}
                            {--to= : End date (Y-m-d), defaults to today}
                            {--employee= : Limit to a single employee ID}';

    protected $description = 'Find attendance worked on public holidays and record compensatory leave credits';

    public function handle(): int
    {
        $from = $this->option('from') ?: now()->startOfMonth()->toDateString();
        $to = $this->option('to') ?: now()->toDateString();

        $this->info("Scanning attendance from {$from} to {$to} for holiday work...");

        $query = Attendance::query()
            ->with('employee')
            ->whereBetween('shift_date', [$from, $to])
            ->whereNotNull('check_in');

        if ($employeeId = $this->option('employee')) {
            $query->where('emp_id', $employeeId);
        }

        $created = 0;
        $failed = 0;

        $query->orderBy('id')->chunkById(500, function ($attendances) use (&$created, &$failed) {
            foreach ($attendances as $attendance) {
                try {
                    $credit = CompensatoryLeaveService::recordForAttendance($attendance);

                    if ($credit && $credit->wasRecentlyCreated) {
                        $created++;
                        $this->line("  + {$credit->employee?->name} earned {$credit->days_earned} day(s) for {$credit->worked_date->toDateString()}");
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("Attendance #{$attendance->id}: {$e->getMessage()}");
                    Log::error('cpl:scan failed', [
                        'attendance_id' => $attendance->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->info("Recorded {$created} new compensatory leave credit(s)." . ($failed ? " {$failed} failed." : ''));

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
