<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeShiftSeeder extends Seeder
{
    public function run()
    {
        $employee = Employee::first();
        $shift = Shift::first();

        if ($employee && $shift) {
            DB::table('employee_shifts')->insert([
                'emp_id' => $employee->id,
                'shift_id' => $shift->id,
                'assigned_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } else {
            echo "No employee or shift found to assign.\n";
        }
    }
}
