<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;

class RoleUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'nadeem.khan@gmail.com')->first();
        $employee = User::where('email', 'john.doe@example.com')->first();

        $adminRole = Role::where('slug', 'admin')->first();
        $employeeRole = Role::where('slug', 'employee')->first();

        if ($admin && $adminRole) {
            DB::table('role_users')->insert([
                'user_id' => $admin->id,
                'role_id' => $adminRole->id,
                // 'created_at' => now(),
                // 'updated_at' => now(),
            ]);
        }

        if ($employee && $employeeRole) {
            DB::table('role_users')->insert([
                'user_id' => $employee->id,
                'role_id' => $employeeRole->id,
                // 'created_at' => now(),
                // 'updated_at' => now(),
            ]);
        }
    }
}
