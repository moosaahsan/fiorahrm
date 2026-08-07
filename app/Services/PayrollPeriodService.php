<?php

namespace App\Services;

use Carbon\Carbon;

class PayrollPeriodService
{
    /**
     * Get the payroll period for the currently ongoing payroll month.
     * 
     * @return array ['start' => Carbon, 'end' => Carbon, 'payroll_month' => int, 'payroll_year' => int]
     */
    public static function current(): array
    {
        return self::getPeriodForDate(Carbon::now());
    }

    /**
     * Get the payroll period for a specific target month.
     * For example, forMonth(2026, 7) returns June 21, 2026 to July 20, 2026.
     * 
     * @param int $year
     * @param int $month
     * @return array ['start' => Carbon, 'end' => Carbon, 'payroll_month' => int, 'payroll_year' => int]
     */
    public static function forMonth(int $year, int $month): array
    {
        $targetDate = Carbon::createFromDate($year, $month, 1);
        
        $start = $targetDate->copy()->subMonthNoOverflow()->startOfMonth()->addDays(20)->startOfDay(); // E.g. June 21
        $end = $targetDate->copy()->startOfMonth()->addDays(19)->endOfDay(); // E.g. July 20
        
        return [
            'start' => $start,
            'end' => $end,
            'payroll_month' => $month,
            'payroll_year' => $year,
        ];
    }

    /**
     * Given an arbitrary date, determines which payroll month it belongs to.
     * 
     * @param Carbon $date
     * @return array ['start' => Carbon, 'end' => Carbon, 'payroll_month' => int, 'payroll_year' => int]
     */
    public static function getPeriodForDate(Carbon $date): array
    {
        $day = $date->day;
        
        if ($day >= 21) {
            // It belongs to the *next* calendar month's payroll
            $payrollMonthDate = $date->copy()->addMonthNoOverflow();
            $year = $payrollMonthDate->year;
            $month = $payrollMonthDate->month;
        } else {
            // It belongs to the *current* calendar month's payroll
            $year = $date->year;
            $month = $date->month;
        }
        
        return self::forMonth($year, $month);
    }
}
