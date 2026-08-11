<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Owns leave entitlement — how many days an employee gets, and when.
 *
 * Policy rules live in data, not here:
 *  - annual entitlement per type  → leave_types.max_days
 *  - eligibility waiting period   → app_settings.leave_eligibility_months
 *
 * Balances are keyed by the leave type *slug*, which is what Leave records and
 * LeaveObserver match on.
 */
class LeaveService
{
    /**
     * Called when an employee is created. Kept as the entry point the
     * EmployeeObserver already uses.
     */
    public static function allocateLeaveForEmployee(Employee $employee): void
    {
        self::syncBalances($employee);
    }

    /**
     * Create or top up an employee's balances for a given year.
     *
     * Safe to run repeatedly: it never touches `used`, and only ever raises an
     * allocation (e.g. when the eligibility waiting period completes). Lowering
     * an entitlement is left to HR so nobody silently loses earned days.
     */
    public static function syncBalances(Employee $employee, ?int $year = null): void
    {
        $year = $year ?? (int) now()->year;
        $eligible = self::isEligible($employee, $year);

        DB::transaction(function () use ($employee, $year, $eligible) {
            foreach (self::activeLeaveTypes() as $leaveType) {
                // Compensatory leave is earned by working a holiday, so it starts
                // at zero and is credited later — but the row must exist so HR can
                // grant against it.
                $entitlement = ($eligible && ! $leaveType->isEarnedOnly())
                    ? (float) $leaveType->max_days
                    : 0.0;

                $balance = LeaveBalance::firstOrNew([
                    'employee_id' => $employee->id,
                    'leave_type' => $leaveType->slug,
                    'year' => $year,
                ]);

                if (! $balance->exists) {
                    $balance->allocated = $entitlement;
                    $balance->used = 0;
                    $balance->remaining = $entitlement;
                    $balance->save();
                    continue;
                }

                // HR set this employee's allocation by hand — leave it alone.
                if ($balance->is_override) {
                    continue;
                }

                $increase = $entitlement - (float) $balance->allocated;

                if ($increase > 0) {
                    $balance->allocated = (float) $balance->allocated + $increase;
                    $balance->remaining = (float) $balance->remaining + $increase;
                    $balance->save();
                }
            }
        });
    }

    /**
     * Set one employee's allocation for a leave type, overriding the entitlement
     * that comes from the leave type itself.
     *
     * Days already taken are never touched — only what is left moves. The row is
     * flagged as an override so the nightly sync does not reset it.
     */
    public static function setAllocation(Employee $employee, string $leaveTypeSlug, float $days, ?int $year = null): LeaveBalance
    {
        $year = $year ?? (int) now()->year;

        $balance = LeaveBalance::firstOrNew([
            'employee_id' => $employee->id,
            'leave_type' => $leaveTypeSlug,
            'year' => $year,
        ]);

        $used = (float) ($balance->used ?? 0);

        $balance->allocated = $days;
        $balance->used = $used;
        // Can go negative if HR allocates less than the employee has already
        // taken — that is real, and better surfaced than silently clamped.
        $balance->remaining = $days - $used;
        $balance->is_override = true;
        $balance->save();

        return $balance;
    }

    /**
     * The employee's balances for a year, keyed by leave type slug.
     *
     * @return \Illuminate\Support\Collection<string, LeaveBalance>
     */
    public static function balancesFor(Employee $employee, ?int $year = null)
    {
        $year = $year ?? (int) now()->year;

        return LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type');
    }

    /**
     * Has the employee completed the configured waiting period by the end of
     * the given year? Entitlement unlocks in full — there is no pro-rata.
     */
    public static function isEligible(Employee $employee, ?int $year = null): bool
    {
        $start = self::entitlementStartDate($employee);

        if (! $start) {
            return false;
        }

        $cutoff = $year ? Carbon::create($year, 12, 31)->endOfDay() : now();

        return $start->lessThanOrEqualTo($cutoff);
    }

    /**
     * The date an employee's leave entitlement unlocks.
     */
    public static function entitlementStartDate(Employee $employee): ?Carbon
    {
        // Fall back to the record's creation date for employees imported without
        // a joining date, so they are not left permanently ineligible.
        $joined = $employee->joining_date ?? $employee->created_at;

        if (! $joined) {
            return null;
        }

        return Carbon::parse($joined)->addMonths(self::eligibilityMonths())->startOfDay();
    }

    public static function eligibilityMonths(): int
    {
        return (int) app_settings('leave_eligibility_months');
    }

    /** @return Collection<int, LeaveType> */
    protected static function activeLeaveTypes(): Collection
    {
        return LeaveType::where('is_active', true)->get();
    }
}
