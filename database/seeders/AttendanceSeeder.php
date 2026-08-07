<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Employee;


class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $employee = Employee::first(); // Get the first employee

        Attendance::create([
            'emp_id' => $employee->id,
            'shift_id' => 1, // Assuming shift 1 exists (Morning shift)
            'check_in' => now()->subDays(1)->setTime(9, 0, 0),
            'check_out' => now()->subDays(1)->setTime(18, 0, 0),
            'late_duration' => 0,
            'manual' => 0
        ]);
    }
}
