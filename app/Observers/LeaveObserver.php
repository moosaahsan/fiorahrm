<?php
namespace App\Observers;

use App\Models\Leave;
use App\Models\LeaveBalance;
use Carbon\Carbon;

class LeaveObserver
{
    public function created(Leave $leave)
    {
        // 1. If created as Approved (Deduct Balance)
        if ($leave->status === 'Approved' && $leave->is_balance_deducted) {
            $this->adjustBalance($leave, false); // Deduct
        }
    }

    public function updated(Leave $leave)
    {
        // 1. Status changed TO Approved AND balance deduction is enabled (but wasn't before)
        if (
            $leave->status === 'Approved' &&
            $leave->is_balance_deducted &&
            !$leave->getOriginal('is_balance_deducted')
        ) {
            $this->adjustBalance($leave, false); // Deduct
        }

        // 2. Status changed FROM Approved (Refund if it was deducted)
        // OR is_balance_deducted was manually unchecked
        if (
            ($leave->getOriginal('status') === 'Approved' && $leave->status !== 'Approved' && $leave->getOriginal('is_balance_deducted')) ||
            ($leave->getOriginal('is_balance_deducted') && !$leave->is_balance_deducted && $leave->getOriginal('status') === 'Approved')
        ) {
             $this->adjustBalance($leave, true); // Refund
        }
    }

    public function deleting(Leave $leave)
    {
        // If an approved/deducted leave is deleted, refund it first!
        if ($leave->is_balance_deducted) {
            $this->adjustBalance($leave, true); // Refund
        }
    }

    /**
     * Centralized logic to increment/decrement balances
     */
    private function adjustBalance(Leave $leave, $isRefund = false)
    {
        $days = (Carbon::parse($leave->start_date)->diffInDays(Carbon::parse($leave->end_date)) + 1);
        if (in_array($leave->day_type, ['first_half', 'second_half', 'half_day'])) {
            $days = $days / 2;
        }

        $year = Carbon::parse($leave->start_date)->year;

        $balance = LeaveBalance::where('employee_id', $leave->employee_id)
            ->where('leave_type', $leave->leave_type)
            ->where('year', $year)
            ->first();

        if ($balance) {
            if ($isRefund) {
                // Refund logic
                $balance->used -= $days;
                $balance->remaining += $days;
                $balance->save();

                // Clear flag (if not deleting)
                $leave->is_balance_deducted = 0;
                $leave->saveQuietly();
            } else {
                // Deduction logic
                if ($balance->remaining >= $days) {
                    $balance->used += $days;
                    $balance->remaining -= $days;
                    $balance->save();

                    // Mark as deducted
                    $leave->is_balance_deducted = 1;
                    $leave->saveQuietly();
                }
            }

            // Also update total_leave_balance on Employee model for quick caching
            $employee = $leave->employee;
            if ($employee) {
                if ($isRefund) {
                    $employee->increment('total_leave_balance', $days);
                } else {
                    $employee->decrement('total_leave_balance', $days);
                }
            }
        }
    }
}
