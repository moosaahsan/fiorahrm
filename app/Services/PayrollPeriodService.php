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
     * The payroll cycle follows the natural calendar month, so
     * forMonth(2026, 7) returns July 1, 2026 to July 31, 2026.
     *
     * @param int $year
     * @param int $month
     * @return array ['start' => Carbon, 'end' => Carbon, 'payroll_month' => int, 'payroll_year' => int]
     */
    public static function forMonth(int $year, int $month): array
    {
        $targetDate = Carbon::createFromDate($year, $month, 1);

        $start = $targetDate->copy()->startOfMonth()->startOfDay(); // E.g. July 1
        $end = $targetDate->copy()->endOfMonth()->endOfDay();       // E.g. July 31 (or 28/29/30)

        return [
            'start' => $start,
            'end' => $end,
            'payroll_month' => $month,
            'payroll_year' => $year,
        ];
    }

    /**
     * Given an arbitrary date, determines which payroll month it belongs to.
     * With calendar-month cycles a date always belongs to its own month.
     *
     * @param Carbon $date
     * @return array ['start' => Carbon, 'end' => Carbon, 'payroll_month' => int, 'payroll_year' => int]
     */
    public static function getPeriodForDate(Carbon $date): array
    {
        return self::forMonth($date->year, $date->month);
    }
}
