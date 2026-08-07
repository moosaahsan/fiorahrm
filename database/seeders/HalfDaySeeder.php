<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HalfDay;
use Carbon\Carbon;

class HalfDaySeeder extends Seeder
{
    public function run()
    {
        HalfDay::create([
            'emp_id' => 3,
            'attendance_id' => 104,
            'shift_id' => 2,
            'date' => Carbon::today(),
            'reason' => 'late',
            'source' => 'auto',
        ]);
    }
}
