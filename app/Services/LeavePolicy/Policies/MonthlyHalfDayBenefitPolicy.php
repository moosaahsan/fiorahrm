<?php

namespace App\Services\LeavePolicy\Policies;

use App\Services\LeavePolicy\PolicyInterface;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;

class MonthlyHalfDayBenefitPolicy implements PolicyInterface
{
    public function getName(): string
    {
        return 'monthly_half_day_benefit';
    }

    public function calculateAdjustment(Employee $employee, Carbon $date): ?float
    {
        $period = \App\Services\PayrollPeriodService::getPeriodForDate($date);

        // Count approved half-days in the given month using standard enum values
        $halfDayCount = Leave::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->whereIn('day_type', ['first_half', 'second_half'])
            ->whereBetween('start_date', [$period['start']->toDateString(), $period['end']->toDateString()])
            ->count();

        // Scenario: Odd number of half-days (1, 3, 5...)
        // We revert 0.5 for the "odd" one to make it fair.
        if ($halfDayCount > 0 && $halfDayCount % 2 !== 0) {
            return 0.5;
        }

        return null;
    }

    public function getReason(Employee $employee, float $amount, Carbon $date): string
    {
        $period = \App\Services\PayrollPeriodService::getPeriodForDate($date);
        $monthName = \Carbon\Carbon::createFromDate($period['payroll_year'], $period['payroll_month'], 1)->format('F');
        $year = $period['payroll_year'];
        
        // We need to re-query count for the reason message or pass it?
        // Let's keep it simple for now as it's called after calculation.
        $halfDayCount = Leave::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->whereIn('day_type', ['first_half', 'second_half'])
            ->whereBetween('start_date', [$period['start']->toDateString(), $period['end']->toDateString()])
            ->count();

        return "Reverting 0.5 leaves as total half-days ($halfDayCount) in $monthName $year was odd. (Policy: Benefit of one half-day per month)";
    }
}
