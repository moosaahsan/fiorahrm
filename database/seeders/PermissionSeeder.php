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
            // Core
            'access-admin-panel',

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
            'add-manual-attendance',
            'edit-attendance-logs',
            'view-leaves',
            'create-leave',
            'approve-leave',
            'reject-leave',
            'view-leave-adjustments',
            'manage-leave-adjustments',
            'view-compensatory-leaves',
            'manage-compensatory-leaves',
            'view-leave-cashouts',
            'manage-leave-cashouts',

            // Payroll Module
            'view-payroll',
            'generate-payroll',
            'approve-payroll',

            // Performance Module
            'view-performance-evaluation',
            'manage-performance-evaluation',
            'view-own-performance',

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
        Permission::whereIn('name', ['add-manual-attendance', 'edit-attendance-logs'])->update(['module' => 'Attendance']);
        Permission::whereIn('name', ['view-payroll', 'generate-payroll', 'approve-payroll'])->update(['module' => 'Payroll']);
        Permission::whereIn('name', ['view-performance-evaluation', 'manage-performance-evaluation', 'view-own-performance'])->update(['module' => 'Performance']);
        Permission::whereIn('name', ['view-compensatory-leaves', 'manage-compensatory-leaves'])->update(['module' => 'Leaves']);
        Permission::whereIn('name', ['view-leave-cashouts', 'manage-leave-cashouts'])->update(['module' => 'Leaves']);

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
            'access-admin-panel',
            'add-manual-attendance',
            'edit-attendance-logs',
            'view-payroll',
            'view-performance-evaluation',
            'manage-performance-evaluation',
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
            'view-compensatory-leaves',
            'manage-compensatory-leaves',
            'view-leave-cashouts',
            'manage-leave-cashouts',
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
            'access-admin-panel',
            'view-performance-evaluation',
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
            'view-compensatory-leaves',
            'view-shift',
            'view-interview',
        ]);

        // Supervisor — oversight & operational role
        $supervisor = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $supervisor->syncPermissions([
            'access-admin-panel',
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
            'view-compensatory-leaves',
            'view-shift',
        ]);

        // HR — full people operations
        $hr = Role::firstOrCreate(['name' => 'hr', 'guard_name' => 'web']);
        $hr->syncPermissions([
            'access-admin-panel',
            'add-manual-attendance',
            'edit-attendance-logs',
            'view-payroll',
            'generate-payroll',
            'view-performance-evaluation',
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
            'view-compensatory-leaves',
            'manage-compensatory-leaves',
            'view-leave-cashouts',
            'manage-leave-cashouts',
            'view-holidays',
            'manage-leave-balances',
            'offboard-employee',
            'view-interview',
            'create-interview',
        ]);

        // Employee — minimal, self-service only
        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);
        $employee->syncPermissions([
            'view-own-performance',
        ]);

        $this->command->info('✅ All permissions and roles seeded successfully!');
    }
}
