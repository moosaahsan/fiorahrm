<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Carbon\Carbon;

class EvaluateHalfDays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:evaluate-half-days';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically evaluate and mark half-days based on lateness or short duration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $today = Carbon::now($timezone)->toDateString();

        $this->info("Evaluating half-days for: $today");

        // Get today's attendances
        $attendances = Attendance::whereDate('check_in', $today)->get();

        if ($attendances->isEmpty()) {
            $this->warn('No attendance records found for today.');
            return;
        }

        foreach ($attendances as $attendance) {
            try {
                app('App\Http\Controllers\Admin\HalfDayController')->evaluateHalfDay($attendance->id);
                $this->line("✔ Processed attendance ID: {$attendance->id}");
            } catch (\Exception $e) {
                $this->error("✖ Failed for attendance ID {$attendance->id}: " . $e->getMessage());
            }
        }

        $this->info('Half-day evaluation complete.');
    }
}
