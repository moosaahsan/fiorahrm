<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Employee;
use App\Models\LeaveSummary;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\EmployeeShift;
use Carbon\Carbon;
use App\Models\EmployeeBreak;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class EmployeeService
{
    public function getByUserId($userId)
    {
        return Employee::where('user_id', $userId)->first();
    }

    public function getEmployees()
    {
        return Employee::all();
    }

    public function getTotalWorkDays($employeeId)
    {
        $period = \App\Services\PayrollPeriodService::current();
        return Attendance::where('emp_id', $employeeId)
            ->whereBetween('check_in', [$period['start'], $period['end']])
            ->count();
    }

    public function getOnTimeDays($employeeId, $onTimeThreshold = '09:00:00')
    {
        $period = \App\Services\PayrollPeriodService::current();
        return Attendance::where('emp_id', $employeeId)
            ->whereBetween('check_in', [$period['start'], $period['end']])
            ->whereTime('check_in', '<=', $onTimeThreshold)
            ->count();
    }

    public function getLateDays($employeeId, $onTimeThreshold = '09:00:00')
    {
        $period = \App\Services\PayrollPeriodService::current();
        return Attendance::where('emp_id', $employeeId)
            ->whereBetween('check_in', [$period['start'], $period['end']])
            ->whereTime('check_in', '>', $onTimeThreshold)
            ->count();
    }

    public function getLeaveStats($employeeId)
    {
        $approved = Leave::where('employee_id', $employeeId)
            ->where('status', 'Approved')
            ->count();

        $cancelled = Leave::where('employee_id', $employeeId)
            ->where('status', 'Cancelled')
            ->count();

        $half = Leave::where('employee_id', $employeeId)
            ->where('leave_type', 'Half')
            ->count();

        $employee = $this->getByUserId(Auth::id());
        $leave_balance = $this->getTotalLeaveBalance($employee);


        return [
            'approved' => $approved,
            'cancelled' => $cancelled,
            'half_as_full' => $half / 2,
            'leave_balance' => $leave_balance,
        ];
    }

    public function getLeaveBalance($employee)
    {
        return $employee->total_leave_balance;
    }

    public function getTotalLeaveBalance($employee)
    {
        $approved = Leave::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->count();
        return $employee->total_leave_balance - $approved;
    }

    public function getOnTimePercentage($employeeId)
    {
        $total = $this->getTotalWorkDays($employeeId);
        if ($total === 0)
            return 0;

        $onTime = $this->getOnTimeDays($employeeId);
        return round(($onTime / $total) * 100, 2);
    }


    public function updateLeaveSummary($empId)
    {
        $year = Carbon::now()->year;

        $approvedLeaves = Leave::where('employee_id', $empId)
            ->where('status', 'Approved')
            ->whereYear('start_date', $year)
            ->get();

        $used = 0;
        $halfCount = 0;

        foreach ($approvedLeaves as $leave) {
            if (in_array($leave->day_type, ['first_half', 'second_half'])) {
                $halfCount++;
                $used += 0.5;
            } else {
                $used++;
            }
        }

        $rejectedCount = Leave::where('employee_id', $empId)
            ->where('status', 'Rejected')
            ->whereYear('start_date', $year)
            ->count();

        // Calculate automated policy adjustments (SaaS-style Policy Engine integration)
        $policyAdjustments = \App\Services\LeavePolicy\LeavePolicyEngine::getAdjustmentsSum($empId, $year);

        LeaveSummary::updateOrCreate(
            ['emp_id' => $empId, 'year' => $year],
            [
                'used_leaves' => $used,
                'half_leaves' => $halfCount,
                'rejected_leaves' => $rejectedCount,
                'policy_adjustments' => $policyAdjustments,
            ]
        );
    }

    public function getLeaveSummary($empId, $year = null)
    {
        $year = $year ?? Carbon::now()->year;
        return LeaveSummary::where('emp_id', $empId)->where('year', $year)->first();
    }

    public function applyLatePenalty($employeeId, $lateLimit = 3)
    {
        $lateDays = $this->getLateDays($employeeId);

        if ($lateDays > $lateLimit) {
            $penaltyLeaves = floor($lateDays / $lateLimit) * 0.5;

            // Optionally: Insert penalty in Leave table
            Leave::create([
                'employee_id' => $employeeId,
                'leave_type' => 'Half',
                'reason' => 'Auto-deducted due to repeated late arrival',
                'status' => 'Approved',
                'start_date' => now(),
                'end_date' => now(),
                'approved_by' => 1, // HR/admin ID
            ]);
        }
    }

    public function getLateLogs($employeeId)
    {
        return Attendance::where('emp_id', $employeeId)
            ->whereNotNull('late_duration')
            ->orderBy('check_in', 'desc')
            ->get(['check_in', 'late_duration']);
    }

    public function getLateDaysGracePeriod($employeeId, $graceMinutes = 5)
    {
        $employee = Employee::with('shift')->findOrFail($employeeId);
        $shiftStart = Carbon::createFromFormat('H:i:s', $employee->shift->start_time)->addMinutes($graceMinutes);

        $period = \App\Services\PayrollPeriodService::current();
        return Attendance::where('emp_id', $employeeId)
            ->whereBetween('check_in', [$period['start'], $period['end']])
            ->whereTime('check_in', '>', $shiftStart->format('H:i:s'))
            ->count();
    }
    public function checkAttendanceStatusForRedirect()
    {
        $employeeId = Auth::user()->employee->id;

        $attendance = Attendance::where('emp_id', $employeeId)
            ->whereDate('created_at', Carbon::today())
            ->first();

        // If attendance exists and check-in is already done, allow access
        if ($attendance && $attendance->check_in) {
            return true;
        }

        // If attendance exists but status is "Absent" and no check-in, redirect
        if ($attendance && $attendance->status === 'Absent' && !$attendance->check_in) {
            return redirect()->route('employee.checkin')->with('message', 'You were marked absent. Please check in to update your status.');
        }

        // If no attendance exists, redirect to check-in page
        if (!$attendance) {
            return redirect()->route('employee.checkin')->with('message', 'Please check in before accessing the dashboard.');
        }

        return true;
    }

    // Note Thhis function is the latest version being used need to polish it further for App Header Checked In Issue 
    public function getAppData($employeeId)
    {
        $employee = Employee::with(['team.department', 'branch', 'user'])->find($employeeId);
        if (!$employee) {
            return null;
        }

        $employeeShift = EmployeeShift::where('emp_id', $employeeId)
            ->with('shift')
            ->latest('created_at')
            ->first();

        if (!$employeeShift || !$employeeShift->shift) {
            return null;
        }

        $shift = $employeeShift->shift;
        $shiftDate = resolve_shift_date($employeeShift);

        $assignedShift = [
            'shift_name' => $shift->shift_name,
            'crosses_midnight' => $shift->crosses_midnight,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time ?? null,
        ];

        if (!$shiftDate) {
            $attStats = get_attendance_stats($employeeId, 'Not Checked In');
            return [
                'emp_id' => $employeeId,
                'employee_id' => $employeeId,
                'attendance_id' => null,
                'attendance_status' => 'Not Checked In',
                'assigned_shift' => $assignedShift,
                'leave_stats' => get_leave_stats($employee),
                'attendance_stats' => $attStats,
                'attendanceStats' => $attStats,
                'break_stats' => calculate_break_stats($employee, $employeeShift, $shiftDate, null),
                'employeeSettings' => get_employee_settings($employeeId),
                'employee_profile' => $this->buildEmployeeProfile($employee),
                'app_settings' => get_app_settings(),
                'isOnBreak' => false,
                'activeBreakId' => null,
            ];
        }

        $attendance = null;
        $attendanceStatus = 'Not Checked In';
        $todayStatus = 'Not Checked In';
        $attendanceStats = get_attendance_stats($employeeId, 'Not Checked In');
        $breakStats = calculate_break_stats($employee, $employeeShift, $shiftDate, null);

        if ($shiftDate) {
            $attendance = Attendance::where('emp_id', $employeeId)
                ->where('shift_date', $shiftDate)
                ->latest()
                ->first();

            $lateThreshold = (int) (get_employee_settings($employeeId, 'late_grace_minutes') ?? 5);
            $earlyThreshold = 60;

            if ($attendance && $attendance->check_in) {
                if ($attendance->check_out) {
                    $attendanceStatus = 'Not Checked In';
                    $todayStatus = 'Checked Out';
                } else {
                    $attendanceStatus = 'Checked In';
                    $todayStatus = getAttendanceStatus(
                        $attendance->check_in,
                        $shift->start_time,
                        $shift->end_time,
                        $earlyThreshold,
                        $lateThreshold,
                        $employeeId,
                        $shiftDate
                    );
                }
            }

            $attendanceStats = get_attendance_stats($employeeId, $todayStatus);
            $breakStats = calculate_break_stats($employee, $employeeShift, $shiftDate, $attendance?->id);
        }

        $activeBreak = ($shiftDate && $attendance && ! $attendance->check_out)
            ? EmployeeBreak::where('emp_id', $employeeId)
                ->where('status', 'On Break')
                ->where('shift_date', $shiftDate)
                ->latest()
                ->first()
            : null;

        return [
            'emp_id' => $employeeId,
            'employee_id' => $employeeId,
            'attendance_id' => ($attendance && ! $attendance->check_out) ? $attendance->id : null,
            'attendance_status' => $attendanceStatus,
            'assigned_shift' => $assignedShift,
            'leave_stats' => get_leave_stats($employee),
            'attendance_stats' => $attendanceStats,
            'attendanceStats' => $attendanceStats,
            'break_stats' => $breakStats,
            'breakStats' => $breakStats,
            'leaveStats' => get_leave_stats($employee),
            'employeeSettings' => get_employee_settings($employeeId),
            'employee_profile' => $this->buildEmployeeProfile($employee),
            'app_settings' => get_app_settings(),
            'isOnBreak' => (bool) $activeBreak,
            'activeBreakId' => $activeBreak?->id,
        ];
    }

    public function buildEmployeeProfile(Employee $employee): array
    {
        $team = null;
        $branch = null;

        if ($employee->team_id) {
            $team = $employee->relationLoaded('team') && $employee->team
                ? $employee->team
                : \App\Models\Team::with('department')->find($employee->team_id);
        }

        if ($employee->branch_id) {
            $branch = $employee->relationLoaded('branch') && $employee->branch
                ? $employee->branch
                : \App\Models\Branch::find($employee->branch_id);
        } elseif ($team?->branch_id) {
            $branch = $team->relationLoaded('branch') && $team->branch
                ? $team->branch
                : \App\Models\Branch::find($team->branch_id);
        }

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'position' => $employee->position,
            'email' => $employee->email ?: $employee->user?->email,
            'profile_pic_url' => get_profile_picture_url($employee->profile_pic),
            'team_id' => $employee->team_id,
            'branch_id' => $employee->branch_id ?: $team?->branch_id,
            'team_name' => $team?->name,
            'branch_name' => $branch?->name,
            'department_name' => $team?->department?->name,
            'team' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
            ] : null,
            'branch' => $branch ? [
                'id' => $branch->id,
                'name' => $branch->name,
            ] : null,
        ];
    }


}
