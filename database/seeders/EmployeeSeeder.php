<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        $user = User::first(); // Assuming you've created at least one user, otherwise use User::create()

        Employee::create([
            'user_id' => $user->id,
            'name' => 'John Doe',
            'position' => 'Software Engineer',
            'joining_date' => now(),
            'email' => 'john.doe@example.com',
            'contact_no' => '123456789',
            'status' => 1,
            'dob' => '1990-01-01',
            'profile_pic' => 'path/to/profile_pic.jpg'
        ]);
    }
}