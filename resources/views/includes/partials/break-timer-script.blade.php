@php
    $employee = \App\Models\Employee::where('user_id', auth()->user()->id)->first();
    $employee_breaks = getEmployeeBreakDetailHelper($employee->id);
    $assignedShift = getTodayAssignedShift($employee->id);
    $shiftDate = web_resolve_shift_date($assignedShift);
    $activeBreak = $employee ? \App\Models\EmployeeBreak::where('emp_id', $employee->id)
        ->where('status', 'On Break')
        ->whereDate('created_at', $shiftDate)
        ->first() : null;
    $initialBreakDuration = $activeBreak ? (int) round(\Carbon\Carbon::parse($activeBreak->start_time)->diffInMinutes(now())) : 0;
@endphp