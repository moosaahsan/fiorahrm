<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch employees with valid users (non-admin)
        $employees = DB::table('employees')
            ->join('users', 'users.id', '=', 'employees.user_id')
            ->where('users.is_admin', 0)
            ->whereNull('employees.deleted_at')
            ->select('employees.id as emp_id')
            ->get();

        foreach ($employees as $employee) {

            // 🔥 All settings defined here
            $settings = [
                "break_duration"              => 30,
                "late_minutes"                => 5,
                "half_day_allowed_in_month"   => 2,
                "full_day_allowed_in_month"   => 2,
                "app_timezone"                => "Asia/Karachi",
                "leaves_allowed_in_year"      => 16,

                // ---- Existing settings you already had ----
                "late_grace_minutes"          => 10,
                "idle_time_allowed"           => 1,
            ];

            foreach ($settings as $name => $value) {
                DB::table('employee_settings')->updateOrInsert(
                    [
                        'emp_id' => $employee->emp_id,
                        'setting_name' => $name,
                    ],
                    [
                        'setting_value' => $value,
                        'updated_by'    => 1,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]
                );
            }
        }
    }
}
