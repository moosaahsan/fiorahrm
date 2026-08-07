<?php
namespace App\Services;

use App\Models\EmployeeShift;
use App\Models\Attendance;
use Carbon\Carbon;

class ShiftAssignmentService
{
    /**
     * Assign shift for a specific employee's attendance
     */
    public function assignShiftToAttendance($empId, $checkIn)
    {
        $shiftAssignment = EmployeeShift::where('emp_id', $empId)
            ->whereDate('assigned_at', '<=', Carbon::parse($checkIn)->toDateString())
            ->orderByDesc('assigned_at')
            ->first();

        if ($shiftAssignment) {
            Attendance::where('emp_id', $empId)
                ->where('check_in', $checkIn)
                ->update(['employee_shift_id' => $shiftAssignment->id]);
        }
    }

    /**
     * Assign shift to all attendances of all employees (can be used in scheduler)
     */
    public function bulkAssignShiftsToAttendances()
    {
        $attendances = Attendance::whereNull('employee_shift_id')->get();

        foreach ($attendances as $attendance) {
            $this->assignShiftToAttendance($attendance->emp_id, $attendance->check_in);
        }
    }
}


// Use the Service When Creating Attendance

// $attendance = Attendance::create([
//     'emp_id' => $employee->id,
//     'check_in' => now(),
//     'check_out' => null,
// ]);

// app(\App\Services\ShiftAssignmentService::class)
//     ->assignShiftToAttendance($employee->id, $attendance->check_in);
