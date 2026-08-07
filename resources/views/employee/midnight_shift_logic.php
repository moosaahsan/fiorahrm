<?php
public function markAttendance(Employee $employee, DateTime $checkIn, ?DateTime $checkOut = null)
{
    $shift = $employee->shifts()->latest('assigned_at')->first();

    $shiftStart = Carbon::parse($checkIn->format('Y-m-d') . ' ' . $shift->start_time);
    $shiftEnd = Carbon::parse($checkIn->format('Y-m-d') . ' ' . $shift->end_time);
    
    // Handle night shift by adding a day if it crosses midnight
    if ($shift->crosses_midnight) {
        $shiftEnd->addDay();
    }

    $isLate = $checkIn->gt($shiftStart->copy()->addMinutes(10)); // e.g. 10 min grace
    $isHalfDay = $isLate && $checkIn->gt($shiftStart->copy()->addHours(3));
    $isAbsent = !$checkIn;

    return Attendance::create([
        'emp_id' => $employee->id,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'is_late' => $isLate,
        'is_half_day' => $isHalfDay,
        'is_absent' => $isAbsent,
    ]);
}
