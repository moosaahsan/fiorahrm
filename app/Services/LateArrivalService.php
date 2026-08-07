<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LateArrival;
use App\Models\CompanyOffDay;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Events\AttendanceUpdated;
use App\Services\EmployeeService;

class LateArrivalService
{
    protected $gracePeriod = 5; // minutes
    protected $lateLimitForDeduction = 3; // count before deducting leave
    protected $leaveToDeduct = 0.5; // half day

    public function trackLateForEmployee(Employee $employee)
    {
        $today = Carbon::today();

        $attendance = Attendance::where('emp_id', $employee->id)
            ->whereDate('check_in', $today)
            ->first();

        if (!$attendance || !$employee->shift) {
            return;
        }

        $checkIn = Carbon::parse($attendance->check_in);
        $shiftStart = Carbon::parse($employee->shift->start_time);

        if ($checkIn->gt($shiftStart->copy()->addMinutes($this->gracePeriod))) {
            LateArrival::firstOrCreate([
                'emp_id' => $employee->id,
                'attendance_id' => $attendance->id,
                'shift_id' => $employee->shift->id,
                'date' => $today->toDateString(),
            ], [
                'scheduled_start' => $shiftStart->toTimeString(),
                'actual_check_in' => $checkIn->toTimeString(),
                'late_minutes' => $checkIn->diffInMinutes($shiftStart),
            ]);
        }
    }

    public function trackLateForAllEmployees()
    {
        $employees = Employee::with('shift')->get();

        foreach ($employees as $employee) {
            $this->trackLateForEmployee($employee);
        }
    }

    public function applyLateDeductions($month = null, $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        $employees = Employee::all();

        foreach ($employees as $employee) {
            $this->applyDeductionForEmployee($employee, $month, $year);
        }
    }

    public function applyDeductionForEmployee(Employee $employee, $month, $year)
    {
        $period = \App\Services\PayrollPeriodService::forMonth($year, $month);
        $lateCount = LateArrival::where('emp_id', $employee->id)
            ->whereBetween('date', [$period['start']->toDateString(), $period['end']->toDateString()])
            ->count();

        $deductions = floor($lateCount / $this->lateLimitForDeduction) * $this->leaveToDeduct;

        if ($deductions > 0 && $employee->total_leave_balance > 0) {
            $employee->decrement('total_leave_balance', min($deductions, $employee->total_leave_balance));
        }
    }

    public function performCheckIn($employeeId, $lateReason = null)
    {
        $employee = Employee::findOrFail($employeeId);

        // Robust shift resolution matching getAppData
        $shiftAssignment = \App\Models\EmployeeShift::where('emp_id', $employeeId)
            ->with('shift')
            ->latest('created_at')
            ->first();

        if (!$shiftAssignment || !$shiftAssignment->shift) {
            throw new \Exception('No shift assigned for today.');
        }

        $shift = $shiftAssignment->shift;
        $bounds = resolve_shift_bounds($shiftAssignment);
        $timezone = $bounds['timezone'];
        $now = Carbon::now($timezone);
        $today = Carbon::today($timezone);
        $referenceDate = $bounds['reference_date'];
        $shiftStart = $bounds['start'];
        $shiftEnd = $bounds['end'];
        $checkInDeadline = resolve_check_in_deadline($shiftAssignment, $referenceDate);
        
        // Prevent check-in before joining date
        if ($employee->joining_date && $today->lt(Carbon::parse($employee->joining_date)->startOfDay())) {
            throw new \Exception('Your joining date is ' . Carbon::parse($employee->joining_date)->format('F j, Y') . '. You cannot check-in before that.');
        }

        // Check if today is a company off day (weekend or holiday) for this team
        if (CompanyOffDay::isOffDay($referenceDate, null, $employee->team_id)) {
            $offDay = CompanyOffDay::getOffDay($referenceDate, null, $employee->team_id);
            throw new \Exception('Today is an off day: ' . ($offDay->note ?? 'Holiday/Off Day'));
        }

        // Prevent check-in before 1 hour of shift start
        $checkInWindowStart = $shiftStart->copy()->subHour();
        if ($now->lt($checkInWindowStart)) {
            throw new \Exception('You can only check in 1 hour before your shift starts.');
        }

        // Allow late / half-day check-in until shift end + grace window
        if ($now->gt($checkInDeadline)) {
            throw new \Exception('Your shift has already ended. You cannot check in anymore.');
        }

        // Check for existing attendance
        $attendance = Attendance::where('emp_id', $employeeId)
            ->where('shift_id', $shift->id)
            ->whereDate('shift_date', $referenceDate)
            ->first();

        if ($attendance && $attendance->check_in) {
            throw new \Exception('Already checked in for this shift.');
        }

        // Create or update attendance record
        if (!$attendance) {
            $attendance = new Attendance();
            $attendance->emp_id = $employeeId;
            $attendance->shift_id = $shift->id;
            $attendance->shift_date = $referenceDate;
            $attendance->created_at = $now;
        } else {
            // If already exists but we are here, it means check_in was null.
            // This is likely an 'Absent' record being converted to 'Present'.
            if ($attendance->status === 'Absent') {
                \Log::info("Converting Absent record to Present for employee: {$employeeId} on date: {$referenceDate}");
            }
        }

        $attendance->check_in = $now;
        $attendance->status = 'Present';
        $attendance->updated_at = $now;

        // Late calculation (standardized with API)
        $gracePeriod = (int) (get_employee_settings($employeeId, 'late_grace_minutes') ?? 5);
        $halfDayThreshold = (int) (get_employee_settings($employeeId, 'mark_half_day_after') ?? 120);

        $lateMinutes = $now->gt($shiftStart->copy()->addMinutes($gracePeriod)) ? $shiftStart->diffInMinutes($now) : 0;

        // Require late_reason if late or half-day (mirroring API)
        if ($lateMinutes > 0 && empty($lateReason)) {
            if ($lateMinutes >= $halfDayThreshold) {
                 throw new \Exception('Half-day check-in requires a reason.');
            } else {
                 throw new \Exception('Late check-in requires a reason.');
            }
        }

        $attendance->late_duration = $lateMinutes;
        $attendance->save();

        // Update any existing leave records for this employee on this shift date to 'Cancelled'
        // This triggers LeaveObserver to refund balance if it was already Approved/Deducted
        \App\Models\Leave::where('employee_id', $employeeId)
            ->where('start_date', $referenceDate)
            ->where('status', '!=', 'Cancelled')
            ->update([
                'status' => 'Cancelled',
                'reason' => 'Auto-cancelled due to late check-in at ' . $now->format('H:i')
            ]);
        
        \Log::info("Cancelled leave records for employee: {$employeeId} on date: {$referenceDate} due to check-in.");

        // Dispatch synchronization event
        $attendanceData = standardize_attendance_data_for_pusher($employeeId);
        \Log::info('Dispatching AttendanceUpdated event on LateArrivalService check-in', ['employee_id' => $employeeId, 'attendance_id' => $attendance->id]);
        event(new AttendanceUpdated($employeeId, $attendanceData));

        // Late Arrival Record
        if ($lateMinutes > 0 && $lateMinutes < $halfDayThreshold) {
            LateArrival::create([
                'emp_id' => $employeeId,
                'attendance_id' => $attendance->id,
                'shift_id' => $shift->id,
                'date' => $referenceDate,
                'scheduled_start' => $shiftStart,
                'actual_check_in' => $now,
                'late_minutes' => $lateMinutes,
                'late_reason' => $lateReason,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } 
        // Auto-Half Day detection (standardized with API)
        elseif ($lateMinutes >= $halfDayThreshold) {
            \App\Models\HalfDay::create([
                'emp_id' => $employeeId,
                'attendance_id' => $attendance->id,
                'shift_id' => $shift->id,
                'date' => $referenceDate,
                'reason' => $lateReason,
                'source' => 'auto',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Check for leave deduction — UNIFIED POLICY: Always deduct for every half-day
            // half_day_allowed_in_month is a LIMIT, not a free quota
            $allowedHalfDays = (int) (get_employee_settings($employeeId, 'allowed_half_days') ?? 2);
            $period = \App\Services\PayrollPeriodService::getPeriodForDate($now);
            $currentMonthStart = $period['start']->toDateString();
            $currentMonthEnd = $period['end']->toDateString();

            $halfDayCount = \App\Models\HalfDay::where('emp_id', $employeeId)
                ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
                ->count();

            // Always deduct 0.5 from leave balance for every half-day
            handleHalfDayDeduction(
                $employeeId,
                $referenceDate,
                $shift,
                $lateReason,
                $now,
                $allowedHalfDays,
                $halfDayCount
            );
        }

        // Return comprehensive app data for state synchronization
        clear_employee_app_data_cache((int) $employeeId);
        $appData = (new EmployeeService())->getAppData($employeeId);

        return array_merge($appData, [
            'message' => 'Checked in successfully.',
            'late_minutes' => $lateMinutes,
            'status' => $lateMinutes >= $halfDayThreshold ? 'Half Day' : ($lateMinutes > 0 ? 'Late' : 'On Time'),
        ]);
    }


    public function performCheckOut($employeeId)
    {
        $now = Carbon::now('Asia/Karachi');

        $employee = Employee::findOrFail($employeeId);
        
        // Robust shift resolution matching getAppData
        $shiftAssignment = \App\Models\EmployeeShift::where('emp_id', $employeeId)
            ->with('shift')
            ->latest('created_at')
            ->first();

        if (!$shiftAssignment || !$shiftAssignment->shift) {
            throw new \Exception('No shift assigned for today.');
        }

        $shift = $shiftAssignment->shift;

        $bounds = resolve_shift_bounds($shiftAssignment);
        $timezone = $bounds['timezone'];
        $now = Carbon::now($timezone);
        $referenceDate = $bounds['reference_date'];
        $shiftStart = $bounds['start'];
        $shiftEnd = $bounds['end'];

        // Fetch today's attendance
        $attendance = Attendance::where('emp_id', $employeeId)
            ->where('shift_id', $shift->id)
            ->whereDate('shift_date', $referenceDate)
            ->first();

        if (!$attendance || !$attendance->check_in) {
            throw new \Exception('Check-in not found for this shift.');
        }

        if ($attendance->check_out) {
            throw new \Exception('Already checked out for this shift.');
        }

        // Ensure check-out is after check-in
        if ($now->lt($attendance->check_in)) {
            throw new \Exception('Cannot check out before check-in time.');
        }

        // Do not allow check-out past shift end + 1 hour (grace)
        $graceEnd = $shiftEnd->copy()->addHour();
        if ($now->gt($graceEnd)) {
            throw new \Exception('Check-out window has expired.');
        }

        // Update attendance
        $attendance->check_out = $now;
        $attendance->working_minutes = $attendance->check_in->diffInMinutes($now);
        $attendance->updated_at = $now;
        $attendance->save();

        // Dispatch synchronization event
        $attendanceData = standardize_attendance_data_for_pusher($employeeId);
        \Log::info('Dispatching AttendanceUpdated event on LateArrivalService check-out', ['employee_id' => $employeeId, 'attendance_id' => $attendance->id]);
        event(new AttendanceUpdated($employeeId, $attendanceData));

        clear_employee_app_data_cache((int) $employeeId);

        return ['message' => 'Checked out successfully.'];
    }
}
