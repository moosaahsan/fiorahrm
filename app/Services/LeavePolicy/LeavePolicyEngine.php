<?php

namespace App\Services\LeavePolicy;

use App\Models\Employee;
use App\Models\LeaveAdjustment;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LeavePolicyEngine
{
    /**
     * @var PolicyInterface[]
     */
    protected array $policies = [];

    public function registerPolicy(PolicyInterface $policy): void
    {
        $this->policies[] = $policy;
    }

    /**
     * Run all registered policies for all active employees.
     */
    public function processAllEmployees(Carbon $month): void
    {
        $employees = Employee::where('status', 1)->get();
        
        foreach ($employees as $employee) {
            $this->processEmployee($employee, $month);
        }
    }

    /**
     * Run all registered policies for a specific employee.
     */
    public function processEmployee(Employee $employee, Carbon $month): void
    {
        foreach ($this->policies as $policy) {
            $this->applyPolicy($policy, $employee, $month);
        }
    }

    /**
     * Apply a specific policy if not already applied.
     */
    protected function applyPolicy(PolicyInterface $policy, Employee $employee, Carbon $date): void
    {
        $month = $date->month;
        $year = $date->year;

        // Check if already applied (Idempotency)
        $exists = LeaveAdjustment::where([
            'emp_id' => $employee->id,
            'policy_name' => $policy->getName(),
            'month' => $month,
            'year' => $year,
        ])->exists();

        if ($exists) {
            Log::info("Policy '{$policy->getName()}' already applied for Employee ID: {$employee->id} for $month/$year");
            return;
        }

        $adjustmentAmount = $policy->calculateAdjustment($employee, $date);

        if ($adjustmentAmount !== null && $adjustmentAmount != 0) {
            DB::transaction(function () use ($policy, $employee, $adjustmentAmount, $date, $month, $year) {
                // 1. Create full audit record
                LeaveAdjustment::create([
                    'emp_id'            => $employee->id,
                    'policy_name'       => $policy->getName(),
                    'adjustment_amount' => $adjustmentAmount,
                    'month'             => $month,
                    'year'              => $year,
                    'applied_at'        => Carbon::now(),
                    'notes'             => $policy->getReason($employee, $adjustmentAmount, $date),
                ]);

                // 2. Directly update leave_balances.remaining so the balance is
                //    immediately visible in the admin panel without manual recalculation.
                //    We increment `remaining` by adjustmentAmount (positive = benefit revert).
                $leaveBalance = LeaveBalance::where('employee_id', $employee->id)
                    ->orderByDesc('remaining')
                    ->first();

                if ($leaveBalance) {
                    $leaveBalance->remaining = $leaveBalance->remaining + $adjustmentAmount;
                    $leaveBalance->save();
                    Log::info("LeaveBalance updated for Employee ID: {$employee->id}. New remaining: {$leaveBalance->remaining}");
                } else {
                    Log::warning("No LeaveBalance found for Employee ID: {$employee->id}. Balance not updated.");
                }
            });

            Log::info("Policy '{$policy->getName()}' applied for Employee ID: {$employee->id}. Adjustment: $adjustmentAmount");
        }
    }

    /**
     * Get total adjustments for an employee for a specific year and month (optional).
     */
    public static function getAdjustmentsSum(int $empId, int $year, ?int $month = null): float
    {
        $query = LeaveAdjustment::where('emp_id', $empId)
            ->where('year', $year);

        if ($month !== null) {
            $query->where('month', $month);
        }

        return (float) $query->sum('adjustment_amount');
    }
}
