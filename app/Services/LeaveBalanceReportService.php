<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Support\Collection;

/**
 * Shared shape for the company-wide leave balance sheet — used by both the
 * on-screen matrix (Admin → Leave Balances) and its Excel export, so the two
 * can never drift apart on what a cell means.
 */
class LeaveBalanceReportService
{
    /**
     * Leave types shown as columns, in a stable order.
     *
     * @return Collection<int, LeaveType>
     */
    public static function reportableLeaveTypes(): Collection
    {
        return LeaveType::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * One employee's balances for a year, keyed by leave type slug.
     *
     * @return array<string, \App\Models\LeaveBalance>
     */
    public static function balancesByType(Employee $employee): array
    {
        return $employee->leaveBalances->keyBy('leave_type')->all();
    }

    /**
     * Allocated / used / remaining for one leave type, defaulting to zero when
     * the employee has no balance row for it yet (e.g. not eligible, or the
     * leave type was added after their balances were last synced).
     *
     * @return array{allocated: float, used: float, remaining: float}
     */
    public static function cell(array $balancesByType, string $slug): array
    {
        $balance = $balancesByType[$slug] ?? null;

        return [
            'allocated' => (float) ($balance->allocated ?? 0),
            'used' => (float) ($balance->used ?? 0),
            'remaining' => (float) ($balance->remaining ?? 0),
        ];
    }

    /**
     * Total remaining across every reportable leave type for one employee —
     * a quick "how many days does this person have left, in total" figure.
     */
    public static function totalRemaining(array $balancesByType, Collection $leaveTypes): float
    {
        $total = 0.0;

        foreach ($leaveTypes as $type) {
            $total += self::cell($balancesByType, $type->slug)['remaining'];
        }

        return $total;
    }
}
