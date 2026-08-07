<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeExitRecord;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReactivateSuspendedEmployees extends Command
{
    protected $signature = 'employees:reactivate-suspended';

    protected $description = 'Reactivate employees whose suspension period has ended';

    public function handle(): int
    {
        $today = Carbon::today();

        $expired = Employee::where('exit_type', 'suspended')
            ->whereNotNull('suspended_end_date')
            ->whereDate('suspended_end_date', '<', $today)
            ->get();

        foreach ($expired as $employee) {
            EmployeeExitRecord::create([
                'emp_id' => $employee->id,
                'exit_date' => $employee->resign_date ?? $employee->suspended_start_date,
                'exit_type' => 'suspended',
                'exit_reason' => $employee->resign_reason,
                'served_notice' => false,
                'suspended_start_date' => $employee->suspended_start_date,
                'suspended_end_date' => $employee->suspended_end_date,
                'processed_by' => $employee->resigned_by,
            ]);

            $employee->resign_date = null;
            $employee->exit_type = null;
            $employee->resign_reason = null;
            $employee->served_notice = null;
            $employee->suspended_start_date = null;
            $employee->suspended_end_date = null;
            $employee->resigned_by = null;
            $employee->save();
        }

        if ($expired->count() > 0) {
            $this->info("Reactivated {$expired->count()} suspended employee(s).");
        }

        return self::SUCCESS;
    }
}
