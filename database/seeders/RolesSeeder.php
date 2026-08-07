<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Str; // Import Str facade

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Create 'Admin' role
        // Role::create([
        //     'slug' => Str::slug('Admin'), // Create slug based on the name
        //     'name' => 'Admin',
        //     'permissions' => json_encode(['manage_employees', 'manage_attendance', 'manage_roles']),
        // ]);

        // // Create 'Employee' role
        // Role::create([
        //     'slug' => Str::slug('Employee'), // Create slug based on the name
        //     'name' => 'Employee',
        //     'permissions' => json_encode(['view_attendance', 'apply_leave']),
        // ]);
    }
}
