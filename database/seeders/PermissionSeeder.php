<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ──────────────────────────────────────────────────────
        // 1. CREATE ALL PERMISSIONS (organized by module)
        // ──────────────────────────────────────────────────────

        $permissions = [
            // Employees Module (Granular)
            'view-employee',
            'create-employee',
            'edit-employee',
            'delete-employee',
            'offboard-employee',
            'manage-leave-balances',
            'manage-employees', // Legacy

            // Organization Module
            'view-department',
            'create-department',
            'edit-department',
            'delete-department',
            'view-departments', // Legacy
            'view-team',
            'create-team',
            'edit-team',
            'delete-team',
            'view-teams', // Legacy
            'view-team-attendance',

            // Attendance Module
            'view-attendance',
            'manage-attendance',
            'view-shift',
            'create-shift',
            'edit-shift',
            'delete-shift',
            'view-shifts', // Legacy
            'view-attendance-logs',
            'view-attendance-sheet',
            'view-late-arrivals',
            'edit-late-arrival',
            'delete-late-arrival',
            'view-half-days',
            'view-breaks',
            'approve-break',
            'reject-break',
            'checkout-employee',
            'view-leaves',
            'create-leave',
            'approve-leave',
            'reject-leave',
            'view-leave-adjustments',
            'manage-leave-adjustments',

            // CRM Module
            'view-leads',
            'manage-leads',
            'view-deals',
            'manage-deals',

            // Settings Module
            'manage-settings',
            'view-holidays',
            'create-holiday',
            'edit-holiday',
            'delete-holiday',
            'view-activity-logs',
            'manage-app-settings',
            'manage-employee-settings',

            // Branch Management
            'view-branch',
            'create-branch',
            'edit-branch',
            'delete-branch',
            'view-interview',
            'create-interview',
            'edit-interview',
            'delete-interview',
            'manage-job-postings',
            'view-bpo-interviews',
            'view-billing-interviews',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Permission::where('name', 'offboard-employee')->update(['module' => 'Employees']);
        Permission::where('name', 'manage-leave-balances')->update(['module' => 'Leaves']);
        Permission::where('name', 'checkout-employee')->update(['module' => 'Attendance']);

        // ──────────────────────────────────────────────────────
        // 2. CREATE ROLES & ASSIGN DEFAULT PERMISSIONS
        // ──────────────────────────────────────────────────────

        // Admin — gets everything (via Gate::before bypass, but also assign all for clarity)
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // Floor Manager — also gets job management and interview permissions
        $floorManager = Role::where('name', 'Floor Manager')->first();
        if ($floorManager) {
            $floorManager->givePermissionTo(['manage-job-postings', 'view-bpo-interviews', 'view-billing-interviews']);
        }

        // Manager — operational control
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        $manager->syncPermissions([
            'view-employee',
            'create-employee',
            'edit-employee',
            'delete-employee',
            'offboard-employee',
            'manage-leave-balances',
            'view-department',
            'create-department',
            'edit-department',
            'delete-department',
            'view-team',
            'create-team',
            'edit-team',
            'delete-team',
            'view-departments',
            'view-teams',
            'view-team-attendance',
            'view-attendance',
            'manage-attendance',
            'view-shift',
            'create-shift',
            'edit-shift',
            'delete-shift',
            'view-shifts',
            'view-attendance-logs',
            'view-attendance-sheet',
            'view-late-arrivals',
            'edit-late-arrival',
            'delete-late-arrival',
            'view-half-days',
            'view-breaks',
            'approve-break',
            'reject-break',
            'checkout-employee',
            'view-leaves',
            'create-leave',
            'approve-leave',
            'reject-leave',
            'view-leave-adjustments',
            'view-holidays',
            'create-holiday',
            'edit-holiday',
            'delete-holiday',
            'manage-app-settings',
            'manage-employee-settings',
            'view-activity-logs',
            'view-branch',
            'view-interview',
            'create-interview',
            'edit-interview',
            'delete-interview',
        ]);

        // Team Lead — team-level visibility & management
        $teamLead = Role::firstOrCreate(['name' => 'team-lead', 'guard_name' => 'web']);
        $teamLead->syncPermissions([
            'view-employee',
            'view-teams',
            'view-team-attendance',
            'view-attendance',
            'view-attendance-logs',
            'view-attendance-sheet',
            'view-late-arrivals',
            'view-half-days',
            'view-breaks',
            'approve-break',
            'reject-break',
            'checkout-employee',
            'view-leaves',
            'create-leave',
            'approve-leave',
            'reject-leave',
            'view-shift',
            'view-interview',
        ]);

        // Supervisor — oversight & operational role
        $supervisor = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $supervisor->syncPermissions([
            'view-employee',
            'view-teams',
            'view-team-attendance',
            'view-attendance',
            'view-attendance-logs',
            'view-attendance-sheet',
            'view-late-arrivals',
            'view-half-days',
            'view-breaks',
            'approve-break',
            'reject-break',
            'view-leaves',
            'create-leave',
            'approve-leave',
            'reject-leave',
            'view-shift',
        ]);

        // HR — full people operations
        $hr = Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $hr->syncPermissions([
            'view-employee',
            'manage-employees',
            'view-departments',
            'view-teams',
            'view-team-attendance',
            'view-attendance',
            'manage-attendance',
            'checkout-employee',
            'view-shifts',
            'view-attendance-logs',
            'view-attendance-sheet',
            'view-late-arrivals',
            'view-half-days',
            'view-breaks',
            'view-leaves',
            'view-holidays',
            'manage-leave-balances',
            'offboard-employee',
            'view-interview',
            'create-interview',
        ]);

        // Employee — minimal, self-service only
        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        // Employees don't need admin panel permissions

        $this->command->info('✅ All permissions and roles seeded successfully!');
    }
}
