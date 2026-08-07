<?php

namespace App\Services\LeavePolicy;

use App\Models\Employee;
use Carbon\Carbon;

interface PolicyInterface
{
    /**
     * Apply the policy for a specific employee and month.
     * Returns the adjustment amount (e.g. 0.5) or null if no adjustment needed.
     */
    public function calculateAdjustment(Employee $employee, Carbon $date): ?float;

    /**
     * Get the unique name of the policy.
     */
    public function getName(): string;

    /**
     * Get a human-readable reason for the adjustment.
     */
    public function getReason(Employee $employee, float $amount, Carbon $date): string;
}
