<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollPolicy;
use App\Models\LateArrival;
use App\Models\EmployeeBreak;
use App\Models\Leave;
use App\Services\LeaveCashoutService;
use Carbon\Carbon;

class PayrollCalculatorService
{
    /**
     * Calculate monthly payroll for a specific employee.
     */
    public function calculateEmployeePayroll($employeeId, $month, $year)
    {
        $employee = Employee::with('salaryStructure')->findOrFail($employeeId);
        $period = \App\Services\PayrollPeriodService::forMonth($year, $month);
        $startDate = $period['start']->copy();
        $endDate = $period['end']->copy();

        $grossSalary = 0;
        $earnings = [];
        $deductions = [];

        if ($employee->salary > 0) {
            $grossSalary = (float) $employee->salary;
            $earnings[] = ['name' => 'Basic/Gross Salary', 'amount' => $grossSalary];
        } elseif ($employee->salaryStructure) {
            $grossSalary = $employee->salaryStructure->basic_salary;
            $earnings[] = ['name' => 'Basic Salary', 'amount' => (float) $grossSalary];

            // Add Allowances
            if ($employee->salaryStructure->allowances) {
                foreach ($employee->salaryStructure->allowances as $name => $amount) {
                    $grossSalary += (float) $amount;
                    $earnings[] = ['name' => $name, 'amount' => (float) $amount];
                }
            }
        }

        // Get applicable policy (Individual > Team > Global)
        $policy = $this->resolvePolicy($employee);

        // 1. Late Arrival Deductions
        $lateDeduction = $this->calculateLateArrivalDeduction($employee, $startDate, $endDate, $policy);
        if ($lateDeduction > 0) {
            $deductions[] = ['name' => 'Late Arrival Fines', 'amount' => $lateDeduction];
        }

        // 2. Unpaid Leave Deductions
        $leaveDeduction = $this->calculateUnpaidLeaveDeduction($employee, $startDate, $endDate, $grossSalary);
        if ($leaveDeduction > 0) {
            $deductions[] = ['name' => 'Unpaid Leave Deductions', 'amount' => $leaveDeduction];
        }

        // 3. Break Exceeded Deductions (Optional placeholder based on requirement)
        $breakDeduction = $this->calculateBreakDeduction($employee, $startDate, $endDate, $policy);
        if ($breakDeduction > 0) {
            $deductions[] = ['name' => 'Break Overstep Fines', 'amount' => $breakDeduction];
        }

        // 4. Year-end leave encashment scheduled for this payroll run
        $cashouts = LeaveCashoutService::payableFor($month, $year, [$employee->id])->get($employee->id, collect());
        foreach ($cashouts as $cashout) {
            $grossSalary += (float) $cashout->amount;
            $earnings[] = ['name' => $cashout->payslipLabel(), 'amount' => (float) $cashout->amount];
        }

        $totalDeductions = collect($deductions)->sum('amount');
        $netSalary = $grossSalary - $totalDeductions;

        return [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'gross_salary' => $grossSalary,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'earnings_detail' => $earnings,
            'deductions_detail' => $deductions,
        ];
    }

    /**
     * Resolve the active policy for the employee.
     */
    private function resolvePolicy($employee)
    {
        // 1. Check Individual
        $policy = PayrollPolicy::where('policy_type', 'Individual')->where('model_id', $employee->id)->where('is_active', true)->first();
        if ($policy)
            return $policy;

        // 2. Check Team
        if ($employee->team_id) {
            $policy = PayrollPolicy::where('policy_type', 'Team')->where('model_id', $employee->team_id)->where('is_active', true)->first();
            if ($policy)
                return $policy;
        }

        // 3. Fallback to Global
        return PayrollPolicy::where('policy_type', 'Global')->where('is_active', true)->first();
    }

    /**
     * Policy: e.g. 3 lates = 0.5 day basic deduction.
     */
    private function calculateLateArrivalDeduction($employee, $start, $end, $policy)
    {
        if (!$policy || !$policy->late_policy)
            return 0;

        $lateCount = LateArrival::where('emp_id', $employee->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $rules = $policy->late_policy; // e.g. {"threshold": 3, "deduction_unit": "day", "deduction_value": 0.5}
        $threshold = $rules['threshold'] ?? 3;

        if ($lateCount >= $threshold) {
            $multiplier = floor($lateCount / $threshold);
            $basicSalary = $employee->salary > 0 ? $employee->salary : ($employee->salaryStructure?->basic_salary ?? 0);
            $dayRate = $basicSalary / 30; // Standard 30-day month
            return round($multiplier * ($rules['deduction_value'] ?? 0.5) * $dayRate, 2);
        }

        return 0;
    }

    /**
     * Deduct for Approved Unpaid Leaves.
     */
    private function calculateUnpaidLeaveDeduction($employee, $start, $end, $grossSalary)
    {
        // Simple pro-rata: (Gross / 30) * Days
        $unpaidDays = Leave::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->whereHas('leaveType', function ($q) {
                $q->where('is_paid', false);
            })
            ->whereBetween('start_date', [$start, $end])
            ->get()
            ->sum(fn (Leave $leave) => $leave->durationInDays());

        if ($unpaidDays > 0) {
            $dayRate = $grossSalary / 30;
            return round($unpaidDays * $dayRate, 2);
        }

        return 0;
    }

    private function calculateBreakDeduction($employee, $start, $end, $policy)
    {
        if (!$policy || !$policy->break_policy)
            return 0;

        $exceededMinutes = EmployeeBreak::where('emp_id', $employee->id)
            ->whereBetween('created_at', [$start, $end])
            ->sum('exceeded_minutes');

        $maxAllowed = $policy->break_policy['max_monthly_exceed'] ?? 0;
        if ($exceededMinutes > $maxAllowed) {
            $finePerMinute = $policy->break_policy['fine_per_minute'] ?? 10; // e.g. 10 PKR per minute
            return ($exceededMinutes - $maxAllowed) * $finePerMinute;
        }

        return 0;
    }
    /**
     * Batch calculate payroll for multiple employees to prevent N+1 query explosion.
     */
    public function calculateBatchPayroll($employeeIds, $month, $year)
    {
        $period = \App\Services\PayrollPeriodService::forMonth($year, $month);
        $startDate = $period['start']->copy();
        $endDate = $period['end']->copy();

        // 1. Pre-fetch all employees with their salary structures
        $employees = Employee::with('salaryStructure')->whereIn('id', $employeeIds)->get();

        // 2. Pre-fetch all necessary data for the entire batch in ONE query each
        $lateArrivalsGrouped = LateArrival::whereIn('emp_id', $employeeIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy('emp_id');

        $leavesGrouped = Leave::whereIn('employee_id', $employeeIds)
            ->where('status', 'Approved')
            ->whereHas('leaveType', fn($q) => $q->where('is_paid', false))
            ->whereBetween('start_date', [$startDate, $endDate])
            ->get()
            ->groupBy('employee_id');

        $breaksGrouped = EmployeeBreak::whereIn('emp_id', $employeeIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy('emp_id');

        // Year-end leave encashment scheduled for this payroll run
        $cashoutsGrouped = LeaveCashoutService::payableFor($month, $year, $employeeIds);

        // 3. Resolve policies for the batch
        // (Optimization: Fetch all policies once)
        $allPolicies = PayrollPolicy::where('is_active', true)->get();

        $results = [];
        foreach ($employees as $employee) {
            $grossSalary = 0;
            $earnings = [];
            $deductions = [];

            if ($employee->salary > 0) {
                $grossSalary = (float) $employee->salary;
                $earnings[] = ['name' => 'Basic/Gross Salary', 'amount' => $grossSalary];
            } elseif ($employee->salaryStructure) {
                $grossSalary = $employee->salaryStructure->basic_salary;
                $earnings[] = ['name' => 'Basic Salary', 'amount' => (float) $grossSalary];
                if ($employee->salaryStructure->allowances) {
                    foreach ($employee->salaryStructure->allowances as $name => $amount) {
                        $grossSalary += (float) $amount;
                        $earnings[] = ['name' => $name, 'amount' => (float) $amount];
                    }
                }
            }

            // Resolve policy from local collection
            $policy = $this->resolvePolicyFromCollection($employee, $allPolicies);

            // 1. Late Arrival
            $lates = $lateArrivalsGrouped->get($employee->id, collect());
            $lateDeduction = $this->calculateLateDeductionFromRecords($employee, $lates, $policy);
            if ($lateDeduction > 0)
                $deductions[] = ['name' => 'Late Arrival Fines', 'amount' => $lateDeduction];

            // 2. Unpaid Leaves
            $leaves = $leavesGrouped->get($employee->id, collect());
            $leaveDeduction = $this->calculateLeaveDeductionFromRecords($grossSalary, $leaves);
            if ($leaveDeduction > 0)
                $deductions[] = ['name' => 'Unpaid Leave Deductions', 'amount' => $leaveDeduction];

            // 3. Break Overstep
            $breaks = $breaksGrouped->get($employee->id, collect());
            $breakDeduction = $this->calculateBreakDeductionFromRecords($breaks, $policy);
            if ($breakDeduction > 0)
                $deductions[] = ['name' => 'Break Overstep Fines', 'amount' => $breakDeduction];

            // 4. Year-end leave encashment
            foreach ($cashoutsGrouped->get($employee->id, collect()) as $cashout) {
                $grossSalary += (float) $cashout->amount;
                $earnings[] = ['name' => $cashout->payslipLabel(), 'amount' => (float) $cashout->amount];
            }

            $totalDeductions = collect($deductions)->sum('amount');
            $results[$employee->id] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'gross_salary' => $grossSalary,
                'total_deductions' => $totalDeductions,
                'net_salary' => $grossSalary - $totalDeductions,
                'earnings_detail' => $earnings,
                'deductions_detail' => $deductions,
            ];
        }

        return $results;
    }

    private function resolvePolicyFromCollection($employee, $allPolicies)
    {
        // Individual
        $policy = $allPolicies->where('policy_type', 'Individual')->where('model_id', $employee->id)->first();
        if ($policy)
            return $policy;

        // Team
        if ($employee->team_id) {
            $policy = $allPolicies->where('policy_type', 'Team')->where('model_id', $employee->team_id)->first();
            if ($policy)
                return $policy;
        }

        // Global
        return $allPolicies->where('policy_type', 'Global')->first();
    }

    private function calculateLateDeductionFromRecords($employee, $lateRecords, $policy)
    {
        if (!$policy || !$policy->late_policy)
            return 0;
        $lateCount = $lateRecords->count();
        $rules = $policy->late_policy;
        $threshold = $rules['threshold'] ?? 3;

        if ($lateCount >= $threshold) {
            $multiplier = floor($lateCount / $threshold);
            $basicSalary = $employee->salary > 0 ? $employee->salary : ($employee->salaryStructure?->basic_salary ?? 0);
            $dayRate = $basicSalary / 30;
            return round($multiplier * ($rules['deduction_value'] ?? 0.5) * $dayRate, 2);
        }
        return 0;
    }

    private function calculateLeaveDeductionFromRecords($grossSalary, $leaveRecords)
    {
        $unpaidDays = $leaveRecords->sum(fn (Leave $leave) => $leave->durationInDays());
        if ($unpaidDays > 0) {
            $dayRate = $grossSalary / 30;
            return round($unpaidDays * $dayRate, 2);
        }
        return 0;
    }

    private function calculateBreakDeductionFromRecords($breakRecords, $policy)
    {
        if (!$policy || !$policy->break_policy)
            return 0;
        $exceededMinutes = $breakRecords->sum('exceeded_minutes');
        $maxAllowed = $policy->break_policy['max_monthly_exceed'] ?? 0;
        if ($exceededMinutes > $maxAllowed) {
            $finePerMinute = $policy->break_policy['fine_per_minute'] ?? 10;
            return ($exceededMinutes - $maxAllowed) * $finePerMinute;
        }
        return 0;
    }
}
