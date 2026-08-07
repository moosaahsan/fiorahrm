<?php

namespace App\Observers;

use App\Models\Employee;
use App\Services\LeaveService;

class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        LeaveService::allocateLeaveForEmployee($employee);
    }
}
