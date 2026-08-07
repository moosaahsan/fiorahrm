<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $year = now()->year;

        // Clear existing data for the current year
        DB::table('leave_balances')->where('year', $year)->delete();

        // Get all employees and leave types
        $employees = Employee::all();
        $leaveTypes = LeaveType::all();

        // foreach ($employees as $employee) {
        //     foreach ($leaveTypes as $leaveType) {
        //         DB::table('leave_balances')->updateOrInsert(
        //             [
        //                 'employee_id' => $employee->id,
        //                 'leave_type' => $leaveType->slug,
        //                 'year' => $year,
        //             ],
        //             [
        //                 'allocated' => $leaveType->max_days,
        //                 'used' => 0,
        //                 'remaining' => $leaveType->max_days,
        //                 'created_at' => now(),
        //                 'updated_at' => now(),
        //             ]
        //         );
        //     }
        // }

        // Optional: Seed only for employee_id = 3 if you want to restrict
        foreach (['3'] as $employeeId) {
            foreach ($leaveTypes as $leaveType) {
                DB::table('leave_balances')->updateOrInsert(
                    [
                        'employee_id' => $employeeId,
                        'leave_type' => $leaveType->slug,
                        'year' => $year,
                    ],
                    [
                        'allocated' => $leaveType->max_days,
                        'used' => 0,
                        'remaining' => $leaveType->max_days,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
