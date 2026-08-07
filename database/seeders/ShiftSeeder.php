<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    public function run()
    {
        // Creating a Morning shift
        Shift::create([
            'shift_name' => 'Morning',  // Corrected column name
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'crosses_midnight' => false, // Default value
        ]);

        // Creating a Night shift
        Shift::create([
            'shift_name' => 'Night',
            'start_time' => '21:00:00',
            'end_time' => '06:00:00',
            'crosses_midnight' => true, // Crosses midnight
        ]);
    }
}
