<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveCashout;
use App\Models\LeaveType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Year-end encashment of unused leave.
 *
 * Nothing carries forward, so whatever is left at the end of a leave year is
 * paid out instead. HR decides the amount — there is deliberately no per-day
 * rate formula, because hotels settle this differently per employee.
 *
 * Creating a cashout consumes the days from leave_balances immediately, so a
 * balance can never be both cashed out and taken as leave.
 */
class LeaveCashoutService
{
    /**
     * Balances still available to cash out for a leave year.
     *
     * Compensatory leave is included — it is a real earned balance — but a type
     * with nothing left is skipped.
     *
     * @return Collection<int, LeaveBalance>
     */
    public static function eligibleBalances(Employee $employee, int $year): Collection
    {
        $alreadyCashedOut = LeaveCashout::where('employee_id', $employee->id)
            ->where('year', $year)
            ->where('status', '!=', LeaveCashout::STATUS_CANCELLED)
            ->pluck('leave_type')
            ->all();

        return LeaveBalance::where('employee_id', $employee->id)
            ->where('year', $year)
            ->where('remaining', '>', 0)
            ->whereNotIn('leave_type', $alreadyCashedOut)
            ->get();
    }

    /**
     * Record a cashout and take the days out of the balance.
     *
     * @throws \InvalidArgumentException when the balance cannot cover the days
     */
    public static function create(
        Employee $employee,
        int $year,
        string $leaveTypeSlug,
        float $days,
        float $amount,
        int $payrollMonth,
        int $payrollYear,
        ?int $processedBy = null,
        ?string $notes = null,
    ): LeaveCashout {
        if ($days <= 0) {
            throw new \InvalidArgumentException('Cashout days must be greater than zero.');
        }

        return DB::transaction(function () use (
            $employee, $year, $leaveTypeSlug, $days, $amount,
            $payrollMonth, $payrollYear, $processedBy, $notes
        ) {
            $balance = LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type', $leaveTypeSlug)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                throw new \InvalidArgumentException("No {$leaveTypeSlug} balance found for {$year}.");
            }

            if ((float) $balance->remaining < $days) {
                throw new \InvalidArgumentException(
                    "Only {$balance->remaining} day(s) remain on {$leaveTypeSlug} for {$year}."
                );
            }

            $cashout = LeaveCashout::create([
                'employee_id' => $employee->id,
                'year' => $year,
                'leave_type' => $leaveTypeSlug,
                'days' => $days,
                'amount' => $amount,
                'status' => LeaveCashout::STATUS_PENDING,
                'payroll_month' => $payrollMonth,
                'payroll_year' => $payrollYear,
                'processed_by' => $processedBy,
                'notes' => $notes,
                'branch_id' => $employee->branch_id,
            ]);

            $balance->used = (float) $balance->used + $days;
            $balance->remaining = (float) $balance->remaining - $days;
            $balance->save();

            return $cashout;
        });
    }

    /**
     * Cancel a cashout that has not been paid yet and return the days.
     *
     * @throws \InvalidArgumentException when the payout has already gone out
     */
    public static function cancel(LeaveCashout $cashout, ?int $cancelledBy = null, ?string $reason = null): LeaveCashout
    {
        if ($cashout->status === LeaveCashout::STATUS_CANCELLED) {
            return $cashout;
        }

        if ($cashout->status === LeaveCashout::STATUS_PAID) {
            throw new \InvalidArgumentException(
                'This cashout has already been paid through payroll and cannot be cancelled here.'
            );
        }

        DB::transaction(function () use ($cashout, $cancelledBy, $reason) {
            $balance = LeaveBalance::where('employee_id', $cashout->employee_id)
                ->where('leave_type', $cashout->leave_type)
                ->where('year', $cashout->year)
                ->lockForUpdate()
                ->first();

            if ($balance) {
                $balance->used = max(0, (float) $balance->used - (float) $cashout->days);
                $balance->remaining = (float) $balance->remaining + (float) $cashout->days;
                $balance->save();
            }

            $cashout->status = LeaveCashout::STATUS_CANCELLED;
            $cashout->processed_by = $cancelledBy ?? $cashout->processed_by;

            if ($reason) {
                $cashout->notes = trim(($cashout->notes ? $cashout->notes . ' | ' : '') . $reason);
            }

            $cashout->save();
        });

        return $cashout->refresh();
    }

    /**
     * Cashouts payable in a given payroll run, keyed by employee.
     *
     * Paid rows stay included so regenerating a payroll produces the same
     * payslip rather than silently dropping the payout.
     *
     * @param  array<int, int>|null  $employeeIds
     * @return Collection<int, Collection<int, LeaveCashout>>
     */
    public static function payableFor(int $payrollMonth, int $payrollYear, ?array $employeeIds = null): Collection
    {
        $query = LeaveCashout::payable()
            ->with('leaveType')
            ->where('payroll_month', $payrollMonth)
            ->where('payroll_year', $payrollYear);

        if ($employeeIds !== null) {
            $query->whereIn('employee_id', $employeeIds);
        }

        return $query->get()->groupBy('employee_id');
    }

    /**
     * Mark a cashout as paid and link it to the payslip line it landed on.
     */
    public static function markPaid(LeaveCashout $cashout, ?int $payrollItemId = null): LeaveCashout
    {
        $cashout->status = LeaveCashout::STATUS_PAID;
        $cashout->payroll_item_id = $payrollItemId;
        $cashout->paid_at = $cashout->paid_at ?? now();
        $cashout->save();

        return $cashout;
    }

    /**
     * Leave types available for cashout, for building HR's dropdowns.
     */
    public static function cashableTypes(): Collection
    {
        return LeaveType::where('is_active', true)->orderBy('name')->get();
    }
}
