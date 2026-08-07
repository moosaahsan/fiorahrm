<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StandardRolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Default Department
        $generalDept = \App\Models\Department::firstOrCreate(
            ['slug' => 'general'],
            ['name' => 'General Department', 'description' => 'Initial department for all existing teams.']
        );

        // Link existing teams to General Department if not linked
        \App\Models\Team::whereNull('department_id')->update(['department_id' => $generalDept->id]);

        // 2. Define Permissions (Spatie uses name as identifier, guard_name default web)
        $permissions = [
            'view-attendance',
            'view-team-attendance',
            'manage-attendance',
            'view-employees',
            'manage-employees',
            'view-leads',
            'manage-leads',
            'view-deals',
            'manage-deals',
            'manage-settings',
        ];

        foreach ($permissions as $pName) {
            \App\Models\Permission::updateOrCreate(
                ['name' => $pName],
                ['guard_name' => 'web']
            );
        }

        // 3. Define Roles and Assign Permissions
        $roles = [
            'admin' => [
                'name' => 'admin',
                'permissions' => \App\Models\Permission::pluck('name')->toArray(),
            ],
            'administrator' => [
                'name' => 'administrator',
                'permissions' => \App\Models\Permission::pluck('name')->toArray(),
            ],
            'manager' => [
                'name' => 'manager',
                'permissions' => ['view-attendance', 'view-team-attendance', 'view-employees', 'view-leads', 'view-deals'],
            ],
            'team-lead' => [
                'name' => 'team-lead',
                'permissions' => ['view-attendance', 'view-team-attendance', 'view-employees'],
            ],
            'employee' => [
                'name' => 'employee',
                'permissions' => ['view-attendance'],
            ],
            'hr' => [
                'name' => 'hr',
                'permissions' => ['view-attendance', 'view-employees', 'manage-employees'],
            ],
            'receptionist' => [
                'name' => 'receptionist',
                'permissions' => ['view-attendance'],
            ],
        ];

        foreach ($roles as $slug => $data) {
            $role = \App\Models\Role::updateOrCreate(
                ['name' => $data['name']],
                ['guard_name' => 'web', 'slug' => $slug]
            );

            // Sync permissions
            $permissionIds = \App\Models\Permission::whereIn('name', $data['permissions'])->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
