<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Admin user create karein
        $admin = User::firstOrCreate(
            ['email' => 'rajausama1991@gmail.com'],
            [
                'name' => 'Raja Usama',
                'password' => Hash::make('@ast2026Feb24'),
            ]
        );

        // 'admin' role ko fetch karein (name = 'admin')
        $adminRole = Role::where('name', 'admin')->first();

        // Pivot table role_users ke zariye role attach karein
        if ($adminRole && !$admin->roles()->where('role_id', $adminRole->id)->exists()) {
            $admin->roles()->attach($adminRole->id);
        }
    }
}
