<?php

namespace App\Services;

use App\Models\CompanyOffDay;
use App\Models\Employee;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Single source of truth for "is this a working day?".
 *
 * A hotel runs seven days a week, so there is no built-in Saturday/Sunday
 * weekend. A day is only off when someone configured it:
 *
 *  - a CompanyOffDay record covering the date (public holiday, closure, …), or
 *  - a weekday listed in the `weekly_off_days` setting, which is empty by
 *    default but lets a business opt back into a fixed weekly off day without
 *    any code change.
 *
 * Off days are scoped by team and branch exactly like CompanyOffDay itself, so
 * a holiday can apply to one team or the whole company.
 */
class WorkingDayService
{
    /**
     * Cached off-day records keyed by "team:branch:year".
     *
     * @var array<string, \Illuminate\Support\Collection<int, CompanyOffDay>>
     */
    protected static array $cache = [];

    /**
     * Weekday numbers (0 = Sunday … 6 = Saturday) that are always off.
     * Empty for the hotel — every weekday is a working day.
     *
     * @var array<int, int>|null
     */
    protected static ?array $weeklyOffDays = null;

    /**
     * Is this a normal working day?
     */
    public static function isWorkingDay($date, ?int $teamId = null, ?int $branchId = null): bool
    {
        return ! self::isOffDay($date, $teamId, $branchId);
    }

    /**
     * Is this date off — either a configured off day or a configured weekly off?
     */
    public static function isOffDay($date, ?int $teamId = null, ?int $branchId = null): bool
    {
        $date = self::toDate($date);

        if (self::isWeeklyOff($date)) {
            return true;
        }

        return self::offDayFor($date, $teamId, $branchId) !== null;
    }

    /**
     * The configured off-day record covering this date, if any.
     *
     * Callers that care about the distinction can inspect `->type`
     * ('Holiday' vs 'Weekend').
     */
    public static function offDayFor($date, ?int $teamId = null, ?int $branchId = null): ?CompanyOffDay
    {
        $date = self::toDate($date);
        $dateString = $date->toDateString();

        foreach (self::offDaysForYear((int) $date->year, $teamId, $branchId) as $offDay) {
            $start = self::toDate($offDay->start_date)->toDateString();
            $end = self::toDate($offDay->end_date)->toDateString();

            if ($dateString >= $start && $dateString <= $end) {
                return $offDay;
            }
        }

        return null;
    }

    /**
     * Is this date a public holiday? This is what compensatory leave is earned
     * against — a weekly off day does not qualify.
     */
    public static function isHoliday($date, ?int $teamId = null, ?int $branchId = null): bool
    {
        $offDay = self::offDayFor($date, $teamId, $branchId);

        return $offDay !== null && $offDay->type === 'Holiday';
    }

    /**
     * Convenience wrappers that pull team/branch scope off an employee.
     */
    public static function isWorkingDayForEmployee($date, ?Employee $employee): bool
    {
        return ! self::isOffDayForEmployee($date, $employee);
    }

    public static function isOffDayForEmployee($date, ?Employee $employee): bool
    {
        return self::isOffDay($date, $employee?->team_id, $employee?->branch_id);
    }

    public static function isHolidayForEmployee($date, ?Employee $employee): bool
    {
        return self::isHoliday($date, $employee?->team_id, $employee?->branch_id);
    }

    /**
     * Number of working days in an inclusive date range.
     */
    public static function countWorkingDays($start, $end, ?int $teamId = null, ?int $branchId = null): int
    {
        $start = self::toDate($start);
        $end = self::toDate($end);

        if ($end->lt($start)) {
            return 0;
        }

        $count = 0;

        foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
            if (self::isWorkingDay($date, $teamId, $branchId)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Is this date a recurring weekly off, ignoring one-off holidays?
     *
     * Callers that already resolve CompanyOffDay records separately use this so
     * the two checks don't overlap.
     */
    public static function isWeeklyOffDay($date): bool
    {
        return self::isWeeklyOff(self::toDate($date));
    }

    /**
     * Weekday numbers configured as a recurring weekly off.
     *
     * @return array<int, int>
     */
    public static function weeklyOffDays(): array
    {
        if (self::$weeklyOffDays !== null) {
            return self::$weeklyOffDays;
        }

        $raw = (string) (app_settings('weekly_off_days') ?? '');

        return self::$weeklyOffDays = collect(explode(',', $raw))
            ->map(fn ($day) => trim($day))
            ->filter(fn ($day) => $day !== '' && is_numeric($day))
            ->map(fn ($day) => (int) $day)
            ->filter(fn ($day) => $day >= 0 && $day <= 6)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Drop memoised state. Call after changing settings or off days mid-request.
     */
    public static function flush(): void
    {
        self::$cache = [];
        self::$weeklyOffDays = null;
    }

    protected static function isWeeklyOff(Carbon $date): bool
    {
        $offDays = self::weeklyOffDays();

        return $offDays !== [] && in_array((int) $date->dayOfWeek, $offDays, true);
    }

    /**
     * Off-day records touching a year, scoped to the given team/branch.
     * Loaded once per scope per request — holidays are few, so this stays cheap
     * even when checking a date per employee per day.
     *
     * @return \Illuminate\Support\Collection<int, CompanyOffDay>
     */
    protected static function offDaysForYear(int $year, ?int $teamId, ?int $branchId)
    {
        $key = ($teamId ?? 'all') . ':' . ($branchId ?? 'all') . ':' . $year;

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        return self::$cache[$key] = CompanyOffDay::query()
            ->whereDate('start_date', '<=', Carbon::create($year, 12, 31)->toDateString())
            ->whereDate('end_date', '>=', Carbon::create($year, 1, 1)->toDateString())
            ->forTeam($teamId)
            ->forBranch($branchId)
            ->get();
    }

    protected static function toDate($date): Carbon
    {
        return $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
    }
}
