<?php

namespace App\Services;

use App\Models\EmployeeBreak;
use App\Models\Employee;
use Carbon\Carbon;

class BreakService
{
    /**
     * Get break details for all employees for a specific date (default: today).
     */
    public function getAllEmployeeBreaks($date = null)
    {
        $date = $date ?? Carbon::today()->toDateString();
 
        return EmployeeBreak::accessible()->with('employee')
            ->whereDate('start_time', $date)
            ->orderBy('start_time', 'asc')
            ->get()
            ->groupBy('emp_id')
            ->map(function ($breaks) {
                return [
                    'employee' => $breaks->first()->employee->name,
                    'total_spent' => $breaks->sum('spent_minutes'),
                    'total_exceeded' => $breaks->sum('exceeded_minutes'),
                    'total_remaining' => $breaks->sum('remaining_minutes'),
                    'breaks' => $breaks,
                ];
            });
    }
 
    /**
     * Get break details for a specific employee by ID (optionally filtered by date).
     */
    public function getEmployeeBreaks($empId, $date = null)
    {
        $date = $date ?? Carbon::today()->toDateString();
 
        $breaks = EmployeeBreak::accessible()->where('emp_id', $empId)
            ->whereDate('start_time', $date)
            ->orderBy('start_time', 'asc')
            ->get();

        return [
            'employee_id' => $empId,
            'total_spent' => $breaks->sum('spent_minutes'),
            'total_exceeded' => $breaks->sum('exceeded_minutes'),
            'total_remaining' => $breaks->sum('remaining_minutes'),
            'breaks' => $breaks,
        ];
    }
}
