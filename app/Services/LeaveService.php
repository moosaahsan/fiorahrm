<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    public static function allocateLeaveForEmployee(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $leaveTypes = LeaveType::all();

            foreach ($leaveTypes as $leaveType) {
                // Check if a LeaveBalance record already exists for this employee and leave type
                $existingBalance = LeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type', $leaveType->name)
                    ->exists();

                if ($existingBalance) {
                    continue; // Skip if record already exists
                }

                // Use default_allocation or fallback to 0 if null
                $allocated = $leaveType->default_allocation ?? 0;

                LeaveBalance::create([
                    'employee_id'    => $employee->id,
                    'leave_type'     => $leaveType->name,
                    'allocated'      => $allocated,
                    'used'           => 0,
                    'remaining'      => $allocated,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        });
    }
}