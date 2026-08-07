<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeBreak;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardMetricsService
{
    public const MAX_SESSION_MINUTES = 960; // 16 hours

    public function batchLeaveBalances(array $employeeIds, int $year): Collection
    {
        if (empty($employeeIds)) {
            return collect();
        }

        $rows = LeaveBalance::whereIn('employee_id', $employeeIds)
            ->where('year', $year)
            ->get()
            ->groupBy('employee_id');

        $fallbackAllocated = (int) (app_settings('leaves_allowed_in_year') ?? 24);

        return collect($employeeIds)->mapWithKeys(function ($empId) use ($rows, $fallbackAllocated) {
            $balance = $rows->get($empId, collect());
            if ($balance->isEmpty()) {
                return [$empId => ['total' => $fallbackAllocated, 'used' => 0, 'remaining' => $fallbackAllocated]];
            }

            return [$empId => [
                'total' => (int) $balance->sum('allocated'),
                'used' => (int) $balance->sum('used'),
                'remaining' => (int) $balance->sum('remaining'),
            ]];
        });
    }

    public function batchWorkingSummaries(Collection $attendancesByEmp, string $timezone, Carbon $now): Collection
    {
        $thisPeriod = \App\Services\PayrollPeriodService::getPeriodForDate($now);
        $startOfThisMonth = $thisPeriod['start']->copy();
        
        $targetDateForLastMonth = \Carbon\Carbon::createFromDate($thisPeriod['payroll_year'], $thisPeriod['payroll_month'], 1)->subMonthNoOverflow();
        $lastPeriod = \App\Services\PayrollPeriodService::forMonth($targetDateForLastMonth->year, $targetDateForLastMonth->month);
        $startOfLastMonth = $lastPeriod['start']->copy();
        $endOfLastMonth = $lastPeriod['end']->copy();
        $startOfThisWeek = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();

        return $attendancesByEmp->map(function ($attendances) use ($timezone, $now, $startOfLastMonth, $endOfLastMonth, $startOfThisMonth, $startOfThisWeek) {
            $totals = ['last_month' => 0, 'this_month' => 0, 'this_week' => 0, 'today' => 0];

            foreach ($attendances as $attendance) {
                $shiftDate = Carbon::parse($attendance->shift_date)->toDateString();
                $minutes = $this->netMinutes($attendance, $timezone, $now);

                if ($shiftDate >= $startOfLastMonth->toDateString() && $shiftDate <= $endOfLastMonth->toDateString()) {
                    $totals['last_month'] += $minutes;
                }
                if ($shiftDate >= $startOfThisMonth->toDateString()) {
                    $totals['this_month'] += $minutes;
                }
                if ($shiftDate >= $startOfThisWeek->toDateString()) {
                    $totals['this_week'] += $minutes;
                }
                if ($shiftDate === $now->toDateString()) {
                    $totals['today'] += $minutes;
                }
            }

            return [
                'last_month' => format_minutes($totals['last_month']),
                'this_month' => format_minutes($totals['this_month']),
                'this_week' => format_minutes($totals['this_week']),
                'today' => format_minutes($totals['today']),
            ];
        });
    }

    public function netMinutes(Attendance $attendance, string $timezone, Carbon $now): int
    {
        if (! $attendance->check_in) {
            return 0;
        }

        $checkIn = Carbon::parse($attendance->check_in, $timezone);

        if ($attendance->check_out) {
            $checkOut = Carbon::parse($attendance->check_out, $timezone);
        } else {
            $shiftDateStr = Carbon::parse($attendance->shift_date)->toDateString();
            $checkOut = $shiftDateStr === $now->toDateString()
                ? $now->copy()
                : $checkIn->copy()->addMinutes(self::MAX_SESSION_MINUTES);
        }

        if ($checkOut->lt($checkIn)) {
            $checkOut->addDay();
        }

        $workedMinutes = min((int) $checkIn->diffInMinutes($checkOut), self::MAX_SESSION_MINUTES);

        if ($attendance->relationLoaded('breaks')) {
            $breakMinutes = (int) $attendance->breaks
                ->where('type', 'General')
                ->where('status', 'Completed')
                ->sum(fn ($b) => min((int) $b->spent_minutes, 120));
        } else {
            $breakMinutes = (int) EmployeeBreak::where('emp_id', $attendance->emp_id)
                ->where('shift_date', $attendance->shift_date)
                ->where('type', 'General')
                ->where('status', 'Completed')
                ->get()
                ->sum(fn ($b) => min((int) $b->spent_minutes, 120));
        }

        return max(0, $workedMinutes - $breakMinutes);
    }

    public function employmentStatusLabel(Employee $employee): string
    {
        if (! $employee->joining_date || ! $employee->probation) {
            return 'Confirmed';
        }

        $probationEnd = Carbon::parse($employee->joining_date)->addMonths((int) $employee->probation);

        return $probationEnd->isFuture() ? 'Probation' : 'Confirmed';
    }
}
