<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\LeaveSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\ShiftSeeder;
use Database\Seeders\UsersSeeder;
use Database\Seeders\RoleUsersSeeder;
use Database\Seeders\EmployeeShiftSeeder;
use Database\Seeders\CompanyOffDaysSeeder;
use Database\Seeders\HalfDaySeeder;
use Database\Seeders\ActivityLogSeeder;
use Database\Seeders\LeaveTypeSeeder;
use Database\Seeders\LeaveBalanceSeeder;
use Database\Seeders\EmployeeSettingsSeeder;
use Database\Seeders\BreakRequestSeeder;






class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            // UsersSeeder::class,
            // RolesSeeder::class,
            // ShiftSeeder::class,
            // EmployeeSeeder::class,
            // AttendanceSeeder::class,
            // LeaveSeeder::class,
            // RoleUsersSeeder::class,
            // EmployeeShiftSeeder::class,
            // CompanyOffDaysSeeder::class
            // HalfDaySeeder::class
            // ActivityLogSeeder::class,
            // LeaveTypeSeeder::class,
            // LeaveBalanceSeeder::class
            // EmployeeSettingsSeeder::class
            BreakRequestSeeder::class, // Break Requests Test Data
        ]);
    }
}
