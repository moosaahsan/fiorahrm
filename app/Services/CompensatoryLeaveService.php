<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CompensatoryLeave;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Compensatory leave (CPL) earned by working on a public holiday.
 *
 * The flow, and where each piece of the audit trail lives:
 *
 *   worked date + days earned  → compensatory_leaves
 *   HR approval / history      → compensatory_leaves.status, approved_by, approved_at
 *   CPL balance & remaining    → leave_balances (leave_type = 'cpl')
 *   CPL usage date             → leaves (leave_type = 'cpl')
 *
 * Reusing leave_balances and leaves means taking CPL goes through exactly the
 * same approval, deduction and refund path as any other leave type.
 *
 * Policy values are configurable in Admin → Settings:
 *   cpl_days_per_holiday, cpl_auto_approve, cpl_validity_days
 */
class CompensatoryLeaveService
{
    /**
     * Record a CPL credit if this attendance falls on a public holiday.
     *
     * Idempotent — one credit per employee per worked date — so it is safe to
     * call from an observer, an import, or a backfill scan.
     */
    public static function recordForAttendance(Attendance $attendance): ?CompensatoryLeave
    {
        if (! self::countsAsWorked($attendance)) {
            return null;
        }

        $employee = $attendance->employee;

        if (! $employee) {
            return null;
        }

        $workedDate = Carbon::parse($attendance->shift_date)->startOfDay();

        $holiday = WorkingDayService::offDayFor($workedDate, $employee->team_id, $employee->branch_id);

        // Only a public holiday earns compensatory leave. A recurring weekly off
        // or a general company off day does not.
        if (! $holiday || $holiday->type !== 'Holiday') {
            return null;
        }

        return self::grant(
            employee: $employee,
            workedDate: $workedDate,
            attendance: $attendance,
            holiday: $holiday,
        );
    }

    /**
     * Create a CPL credit. Used both by auto-detection and by HR granting one
     * by hand. Returns the existing record if one is already on file.
     */
    public static function grant(
        Employee $employee,
        $workedDate,
        ?Attendance $attendance = null,
        $holiday = null,
        ?float $days = null,
        ?string $notes = null,
    ): CompensatoryLeave {
        $workedDate = Carbon::parse($workedDate)->startOfDay();

        $existing = CompensatoryLeave::where('employee_id', $employee->id)
            ->whereDate('worked_date', $workedDate)
            ->first();

        if ($existing) {
            return $existing;
        }

        $credit = new CompensatoryLeave([
            'employee_id' => $employee->id,
            'attendance_id' => $attendance?->id,
            'company_off_day_id' => $holiday?->id,
            'worked_date' => $workedDate->toDateString(),
            'holiday_title' => $holiday?->note ?: ($holiday?->title ?? null),
            'days_earned' => $days ?? self::daysPerHoliday(),
            'status' => CompensatoryLeave::STATUS_PENDING,
            'notes' => $notes,
            'branch_id' => $employee->branch_id,
        ]);

        $credit->save();

        if (self::autoApproves()) {
            self::approve($credit, null);
        }

        return $credit->refresh();
    }

    /**
     * Approve a credit and add it to the employee's CPL balance.
     */
    public static function approve(CompensatoryLeave $credit, ?int $approvedBy = null): CompensatoryLeave
    {
        if ($credit->status === CompensatoryLeave::STATUS_APPROVED) {
            return $credit;
        }

        DB::transaction(function () use ($credit, $approvedBy) {
            $credit->status = CompensatoryLeave::STATUS_APPROVED;
            $credit->approved_by = $approvedBy;
            $credit->approved_at = now();
            $credit->expires_at = self::expiryFor($credit);
            $credit->save();

            self::creditBalance($credit);
        });

        return $credit->refresh();
    }

    /**
     * Reject a credit. If it had already been added to the balance, take it back
     * — but never below what the employee has left, so an already-taken day is
     * not silently turned into a negative balance.
     */
    public static function reject(CompensatoryLeave $credit, ?int $rejectedBy = null, ?string $reason = null): CompensatoryLeave
    {
        if ($credit->status === CompensatoryLeave::STATUS_REJECTED) {
            return $credit;
        }

        DB::transaction(function () use ($credit, $rejectedBy, $reason) {
            if ($credit->is_credited) {
                self::debitBalance($credit);
            }

            $credit->status = CompensatoryLeave::STATUS_REJECTED;
            $credit->approved_by = $rejectedBy;
            $credit->approved_at = now();

            if ($reason) {
                $credit->notes = trim(($credit->notes ? $credit->notes . ' | ' : '') . $reason);
            }

            $credit->save();
        });

        return $credit->refresh();
    }

    /**
     * The employee's current CPL balance row for a year.
     */
    public static function balanceFor(Employee $employee, ?int $year = null): LeaveBalance
    {
        $year = $year ?? (int) now()->year;

        return LeaveBalance::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type' => LeaveType::CPL_SLUG,
                'year' => $year,
            ],
            [
                'allocated' => 0,
                'used' => 0,
                'remaining' => 0,
            ]
        );
    }

    // ──────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────

    /**
     * Did the employee actually work this day? A leave or an absent record on a
     * holiday must not earn a credit.
     */
    protected static function countsAsWorked(Attendance $attendance): bool
    {
        if (! $attendance->shift_date) {
            return false;
        }

        if (! $attendance->check_in) {
            return false;
        }

        return in_array($attendance->status, ['Present', 'Late', 'Half Day'], true);
    }

    protected static function creditBalance(CompensatoryLeave $credit): void
    {
        if ($credit->is_credited) {
            return;
        }

        $employee = $credit->employee ?? Employee::find($credit->employee_id);

        if (! $employee) {
            throw new ModelNotFoundException("Employee #{$credit->employee_id} not found for CPL credit #{$credit->id}");
        }

        $balance = self::balanceFor($employee, (int) $credit->worked_date->year);

        $days = (float) $credit->days_earned;

        $balance->allocated = (float) $balance->allocated + $days;
        $balance->remaining = (float) $balance->remaining + $days;
        $balance->save();

        $credit->is_credited = true;
        $credit->save();
    }

    protected static function debitBalance(CompensatoryLeave $credit): void
    {
        $employee = $credit->employee ?? Employee::find($credit->employee_id);

        if (! $employee) {
            return;
        }

        $balance = self::balanceFor($employee, (int) $credit->worked_date->year);

        // Only claw back what is still unused.
        $days = min((float) $credit->days_earned, (float) $balance->remaining);

        $balance->allocated = max(0, (float) $balance->allocated - (float) $credit->days_earned);
        $balance->remaining = max(0, (float) $balance->remaining - $days);
        $balance->save();

        $credit->is_credited = false;
        $credit->save();
    }

    protected static function expiryFor(CompensatoryLeave $credit): ?Carbon
    {
        $validityDays = self::validityDays();

        if ($validityDays <= 0) {
            return null;
        }

        return Carbon::parse($credit->worked_date)->addDays($validityDays);
    }

    public static function daysPerHoliday(): float
    {
        return (float) (app_settings('cpl_days_per_holiday') ?: 1);
    }

    public static function autoApproves(): bool
    {
        return (bool) app_settings('cpl_auto_approve');
    }

    public static function validityDays(): int
    {
        return (int) app_settings('cpl_validity_days');
    }
}
