<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeShift;
use Carbon\Carbon;

class ShiftService
{
    public function getCurrentShift($empId)
    {
        $assignment = EmployeeShift::where('emp_id', $empId)
            ->orderByDesc('assigned_at')
            ->with('shift')
            ->first();

        return $assignment?->shift;
    }

    public function getShiftHistory($empId)
    {
        return EmployeeShift::where('emp_id', $empId)
            ->orderByDesc('assigned_at')
            ->with('shift')
            ->get();
    }

    public function assignShift($empId, $shiftId, $assignedAt = null)
    {
        return EmployeeShift::create([
            'emp_id' => $empId,
            'shift_id' => $shiftId,
            'assigned_at' => $assignedAt ?? Carbon::now(),
        ]);
    }

    // Example Usage
//     $employee = Employee::find($id);

    // // Get current shift
// $currentShift = $employee->currentShiftAssignment?->shift;

    // // Assign new shift
// app(ShiftService::class)->assignShift($employee->id, $newShiftId);

    // // Get full shift history
// $history = app(ShiftService::class)->getShiftHistory($employee->id);

}
