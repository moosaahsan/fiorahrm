<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CompanyOffDay;
use Carbon\Carbon;

class CompanyOffDaysSeeder extends Seeder
{
    public function run()
    {
        $holidays = [
            [
                'start_date' => '2025-03-23',
                'end_date'   => '2025-03-23',
                'type'       => 'Holiday',
                'note'       => 'Pakistan Day'
            ],
            [
                'start_date' => '2025-05-01',
                'end_date'   => '2025-05-01',
                'type'       => 'Holiday',
                'note'       => 'Labour Day'
            ],
            [
                'start_date' => '2025-12-25',
                'end_date'   => '2025-12-26',
                'type'       => 'Holiday',
                'note'       => 'Christmas'
            ],
            // add more holidays here if needed
        ];

        foreach ($holidays as $day) {
            CompanyOffDay::updateOrCreate(
                [
                    'start_date' => $day['start_date'],
                    'end_date'   => $day['end_date'],
                ],
                [
                    'type' => $day['type'],
                    'note' => $day['note']
                ]
            );
        }
    }
}
