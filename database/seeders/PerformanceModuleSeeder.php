<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Performance\PerformanceSetting;

class PerformanceModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed default performance weighting settings
        $settings = [
            [
                'key' => 'attendance_weight',
                'value' => 15.00,
                'description' => 'Maximum score for perfect monthly attendance discipline'
            ],
            [
                'key' => 'leave_weight',
                'value' => 15.00,
                'description' => 'Maximum score for not taking unpaid/unapproved leaves'
            ],
            [
                'key' => 'break_weight',
                'value' => 10.00,
                'description' => 'Maximum score for keeping breaks within allowed duration limit'
            ],
            [
                'key' => 'late_weight',
                'value' => 10.00,
                'description' => 'Maximum score for minimal/no late arrivals'
            ],
            [
                'key' => 'dress_code_weight',
                'value' => 10.00,
                'description' => 'Maximum score for manual dress code evaluation'
            ],
            [
                'key' => 'work_performance_weight',
                'value' => 20.00,
                'description' => 'Maximum score for manual work performance evaluation'
            ],
            [
                'key' => 'behavior_weight',
                'value' => 20.00,
                'description' => 'Maximum score for manual professional behavior / teamwork'
            ],
        ];

        foreach ($settings as $s) {
            PerformanceSetting::updateOrCreate(['key' => $s['key']], $s);
        }

        // 2. Define performance permissions
        $permissions = [
            'view-performance-evaluation',
            'manage-performance-evaluation',
            'view-own-performance',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 3. Assign permissions to Roles
        // Admin gets all
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // Manager
        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            $manager->givePermissionTo(['view-performance-evaluation', 'manage-performance-evaluation']);
        }

        // Team Lead
        $teamLead = Role::where('name', 'team-lead')->first();
        if ($teamLead) {
            $teamLead->givePermissionTo(['view-performance-evaluation']);
        }

        // Employee
        $employee = Role::where('name', 'employee')->first();
        if ($employee) {
            $employee->givePermissionTo(['view-own-performance']);
        }
    }
}
