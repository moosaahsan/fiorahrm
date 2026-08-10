<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveType;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the seeder.
     *
     * max_days is the annual entitlement used when allocating balances.
     * Carry forward is disabled across the board — unused balance is cashed
     * out at year end instead.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Sick Leave',
                'slug' => 'sick',
                'description' => 'Leave for medical reasons',
                'max_days' => 10,
                'auto_allocate' => true,
            ],
            [
                'name' => 'Casual Leave',
                'slug' => 'casual',
                'description' => 'Leave for personal reasons',
                'max_days' => 10,
                'auto_allocate' => true,
            ],
            [
                'name' => 'Annual Leave',
                'slug' => 'annual',
                'description' => 'Annual vacation entitlement',
                'max_days' => 15,
                'auto_allocate' => true,
            ],
            [
                // Earned by working on a public holiday, never allocated up front.
                'name' => 'Compensatory Leave',
                'slug' => 'cpl',
                'description' => 'Earned by working on a public holiday',
                'max_days' => 0,
                'auto_allocate' => false,
            ],
            [
                'name' => 'Maternity Leave',
                'slug' => 'maternity',
                // Capped, but granted case by case rather than allocated to everyone.
                'description' => 'Leave for maternity',
                'max_days' => 90,
                'auto_allocate' => false,
            ],
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::updateOrCreate(['slug' => $type['slug']], $type + [
                'is_paid' => true,
                'carry_forward' => false,
                'max_carry_forward_days' => 0,
                'requires_approval' => true,
                'is_active' => true,
            ]);
        }
    }
}
