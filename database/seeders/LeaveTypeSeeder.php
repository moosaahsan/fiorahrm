<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the seeder.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Casual Leave',
                'slug' => 'casual',
                'description' => 'Leave for personal reasons',
                'max_days' => 7,
                'is_paid' => true,
                'carry_forward' => true,
                'max_carry_forward_days' => 3,
                'requires_approval' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sick Leave',
                'slug' => 'sick',
                'description' => 'Leave for medical reasons',
                'max_days' => 7,
                'is_paid' => true,
                'carry_forward' => false,
                'max_carry_forward_days' => 0,
                'requires_approval' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Annual Leave',
                'slug' => 'annual',
                'description' => 'Leave for vacation',
                'max_days' => 14,
                'is_paid' => true,
                'carry_forward' => true,
                'max_carry_forward_days' => 5,
                'requires_approval' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Maternity Leave',
                'slug' => 'maternity',
                'description' => 'Leave for maternity',
                'max_days' => 90,
                'is_paid' => true,
                'carry_forward' => false,
                'max_carry_forward_days' => 0,
                'requires_approval' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}