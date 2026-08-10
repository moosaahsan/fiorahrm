<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\LeaveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Keeps leave balances in step with the configured policy.
 *
 * Running this daily covers two cases without any extra bookkeeping:
 *  - an employee completes the eligibility waiting period and their
 *    entitlement unlocks in full
 *  - the calendar year rolls over and fresh balances are needed
 *
 * The sync is idempotent, so re-running it is always safe.
 */
class SyncLeaveBalances extends Command
{
    protected $signature = 'leaves:sync-balances
                            {--year= : Calendar year to sync (defaults to the current year)}
                            {--employee= : Limit the sync to a single employee ID}';

    protected $description = 'Create or top up leave balances based on leave types and eligibility settings';

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: now()->year);

        $query = Employee::query()->where('status', 1);

        if ($employeeId = $this->option('employee')) {
            $query->where('id', $employeeId);
        }

        $synced = 0;
        $failed = 0;

        $this->info("Syncing leave balances for {$year}...");

        $query->orderBy('id')->chunkById(200, function ($employees) use ($year, &$synced, &$failed) {
            foreach ($employees as $employee) {
                try {
                    LeaveService::syncBalances($employee, $year);
                    $synced++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("Employee #{$employee->id}: {$e->getMessage()}");
                    Log::error('SyncLeaveBalances failed', [
                        'employee_id' => $employee->id,
                        'year' => $year,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->info("Synced {$synced} employee(s)." . ($failed ? " {$failed} failed." : ''));

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
