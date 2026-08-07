<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\Leave;
use App\Models\LeaveDay;

class LeaveSeeder extends Seeder
{
    public function run(): void
    {
        $leave = Leave::create([
            'employee_id' => 3,
            'leave_type' => 'casual',
            'start_date' => Carbon::parse('2025-08-10'),
            'end_date' => Carbon::parse('2025-08-14'),
            'status' => 'Pending',
            'reason' => 'Family emergency leave for 5 days',
            'approved_by' => null,
            'shift_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create leave_days for each date
        $start = Carbon::parse($leave->start_date);
        $end = Carbon::parse($leave->end_date);

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            LeaveDay::create([
                'leave_id' => $leave->id,
                'leave_date' => $date->toDateString(),
                'status' => 'Pending',
            ]);
        }
    }
}
