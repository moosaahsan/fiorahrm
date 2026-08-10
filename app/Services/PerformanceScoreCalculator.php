<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\EmployeeBreak;
use App\Models\LateArrival;
use App\Models\CompanyOffDay;
use App\Models\Performance\PerformanceSetting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PerformanceScoreCalculator
{
    /**
     * Calculate automatic and total performance metrics for an employee for a given month.
     *
     * @param Employee $employee
     * @param int $year
     * @param int $month
     * @return array
     */
    public function calculate(Employee $employee, int $year, int $month): array
    {
        // 1. Load weights
        $attendanceWeight = (double) PerformanceSetting::getVal('attendance_weight', 15.00);
        $leaveWeight      = (double) PerformanceSetting::getVal('leave_weight', 15.00);
        $breakWeight      = (double) PerformanceSetting::getVal('break_weight', 10.00);
        $lateWeight       = (double) PerformanceSetting::getVal('late_weight', 10.00);

        // Date calculations
        $timezone = get_employee_settings($employee->id, 'time_zone') ?? 'Asia/Karachi';
        $startDate = Carbon::create($year, $month, 1, 0, 0, 0, $timezone);
        $endDate = $startDate->copy()->endOfMonth();
        
        $limitDate = $endDate->lt(now($timezone)) ? $endDate : now($timezone);

        // 2. Scheduled Working Days (excluding configured off days)
        $totalWorkingDays = WorkingDayService::countWorkingDays(
            $startDate->toDateString(),
            $limitDate->toDateString(),
            $employee->team_id,
            $employee->branch_id
        );

        // 3. Attendance Scored
        // Get Present Days
        $attendances = Attendance::where('emp_id', $employee->id)
            ->whereBetween('shift_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();
            
        $presentDays = $attendances->where('status', 'Present')->count();

        if ($totalWorkingDays > 0) {
            $attendanceRatio = min(1.0, $presentDays / $totalWorkingDays);
            $attendanceScore = round($attendanceRatio * $attendanceWeight, 2);
        } else {
            $attendanceScore = $attendanceWeight; // Default to max score if no working days
        }

        // 4. Leave Scored (Deduct 3.0 points for each approved leave day)
        // Count distinct leave days within this month
        $leaves = Leave::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                  ->orWhereBetween('end_date', [$startDate->toDateString(), $endDate->toDateString()]);
            })
            ->get();

        $leaveDaysCount = 0;
        foreach ($leaves as $leave) {
            // Find intersection between leave dates and the target month
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            
            if ($start->lt($startDate)) {
                $start = $startDate->copy();
            }
            if ($end->gt($endDate)) {
                $end = $endDate->copy();
            }
            $leaveDaysCount += $start->diffInDays($end) + 1;
        }

        $leaveDeduction = $leaveDaysCount * 3.0;
        $leaveScore = max(0.00, round($leaveWeight - $leaveDeduction, 2));

        // 5. Break Scored (Deduct 2.0 points for each exceeded break day)
        $exceededBreaksCount = EmployeeBreak::where('emp_id', $employee->id)
            ->whereBetween('shift_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('exceeded_minutes', '>', 0)
            ->count();

        $breakDeduction = $exceededBreaksCount * 2.0;
        $breakScore = max(0.00, round($breakWeight - $breakDeduction, 2));

        // 6. Late Arrivals Scored (Deduct 2.0 points for each late arrival)
        $lateArrivalsCount = LateArrival::where('emp_id', $employee->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->count();

        $lateDeduction = $lateArrivalsCount * 2.0;
        $lateScore = max(0.00, round($lateWeight - $lateDeduction, 2));

        // Combine Automated Metrics
        $autoScore = round($attendanceScore + $leaveScore + $breakScore + $lateScore, 2);

        return [
            'total_working_days' => $totalWorkingDays,
            'present_days' => $presentDays,
            'leave_days' => $leaveDaysCount,
            'exceeded_breaks' => $exceededBreaksCount,
            'late_arrivals' => $lateArrivalsCount,
            
            // Raw scores
            'attendance_score' => $attendanceScore,
            'leave_score' => $leaveScore,
            'break_score' => $breakScore,
            'late_score' => $lateScore,
            'auto_score' => $autoScore,
        ];
    }
}
