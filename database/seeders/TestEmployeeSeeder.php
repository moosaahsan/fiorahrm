<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ER\ErDepartment;
use App\Models\ER\ErCampaign;
use App\Models\ER\ErEmployee;
use App\Models\ER\ErEmployeeBank;
use App\Models\ER\ErDailyReport;
use Illuminate\Support\Str;

class TestEmployeeSeeder extends Seeder
{
    public function run()
    {
        // 1. Ensure Department Exists
        $dept = ErDepartment::firstOrCreate([
            'name' => 'Clsr_CPA'
        ], [
            'slug' => Str::slug('Clsr_CPA'),
            'description' => 'Test Department',
            'is_active' => true
        ]);

        // 2. Ensure Campaign Exists
        $campaign = ErCampaign::firstOrCreate([
            'name' => 'CPA Closers Working'
        ], [
            'er_department_id' => $dept->id,
            'slug' => Str::slug('CPA Closers Working') . '-' . Str::random(4),
            'description' => 'Test Campaign',
            'is_active' => true
        ]);

        // 3. Create or Get the Employee (Hamid Arshad)
        $employee = ErEmployee::firstOrCreate([
            'cnic' => '37405-0892115-7'
        ], [
            'name' => 'Hamid Arshad',
            'er_campaign_id' => $campaign->id,
            'designation' => 'Closer',
            'title' => 'Title 3', // Based on Title# 3 in the sheet
            'base_salary' => 65000,
            'joining_date' => '2026-02-16',
            'status' => 'active'
        ]);

        // 4. Update Bank Details
        ErEmployeeBank::updateOrCreate([
            'er_employee_id' => $employee->id
        ], [
            'cnic' => '37405-0892115-7',
            'bank_name' => 'JS Bank',
            'account_number' => '0002575176',
        ]);

        // 5. Daily Reports (June 22 - June 28)
        $reports = [
            // June 22
            ['report_date' => '2026-06-22', 'status' => 'P', 'check_in' => '17:38', 'check_out' => '02:00', 'working_hours' => 8.44, 'dialing_time' => 8.44, 'calls' => 324, 'transfers' => 27, 'cpa' => 0, 'cpl' => 24, '1200_plus' => 11, 'bonus' => 3000],
            // June 23
            ['report_date' => '2026-06-23', 'status' => 'P', 'check_in' => '17:41', 'check_out' => '02:00', 'working_hours' => 8.25, 'dialing_time' => 8.25, 'calls' => 265, 'transfers' => 24, 'cpa' => 0, 'cpl' => 18, '1200_plus' => 7, 'bonus' => 0],
            // June 24
            ['report_date' => '2026-06-24', 'status' => 'A', 'check_in' => null, 'check_out' => null, 'working_hours' => 0, 'dialing_time' => 0, 'calls' => 0, 'transfers' => 0, 'cpa' => 0, 'cpl' => 0, '1200_plus' => 0, 'bonus' => 0],
            // June 25
            ['report_date' => '2026-06-25', 'status' => 'A', 'check_in' => null, 'check_out' => null, 'working_hours' => 0, 'dialing_time' => 0, 'calls' => 0, 'transfers' => 0, 'cpa' => 0, 'cpl' => 0, '1200_plus' => 0, 'bonus' => 0],
            // June 26
            ['report_date' => '2026-06-26', 'status' => 'A', 'check_in' => null, 'check_out' => null, 'working_hours' => 0, 'dialing_time' => 0, 'calls' => 0, 'transfers' => 0, 'cpa' => 0, 'cpl' => 0, '1200_plus' => 0, 'bonus' => 0],
            // June 27
            ['report_date' => '2026-06-27', 'status' => 'OFF', 'check_in' => null, 'check_out' => null, 'working_hours' => 0, 'dialing_time' => 0, 'calls' => 0, 'transfers' => 0, 'cpa' => 0, 'cpl' => 0, '1200_plus' => 0, 'bonus' => 0],
            // June 28
            ['report_date' => '2026-06-28', 'status' => 'OFF', 'check_in' => null, 'check_out' => null, 'working_hours' => 0, 'dialing_time' => 0, 'calls' => 0, 'transfers' => 0, 'cpa' => 0, 'cpl' => 0, '1200_plus' => 0, 'bonus' => 0],
        ];

        foreach ($reports as $r) {
            ErDailyReport::updateOrCreate([
                'er_employee_id' => $employee->id,
                'report_date' => $r['report_date']
            ], [
                'check_in' => $r['check_in'],
                'check_out' => $r['check_out'],
                'attendance_status' => $r['status'],
                'working_hours' => $r['working_hours'],
                'dialing_time' => $r['dialing_time'],
                'calls' => $r['calls'],
                'transfers' => $r['transfers'],
                'cpa' => $r['cpa'],
                'cpl' => $r['cpl'],
                'twelve_hundred_plus' => $r['1200_plus'],
                'daily_bonus' => $r['bonus'],
                'entered_by' => 1 // Assuming 1 is the Admin ID
            ]);
        }
    }
}
