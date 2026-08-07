<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AppSetting;
class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        AppSetting::updateOrCreate(
            ['key' => 'company_name'],
            ['value' => 'Your Company Name', 'type' => 'string', 'description' => 'Name of the company']
        );

        AppSetting::updateOrCreate(
            ['key' => 'app_logo'],
            ['value' => '/images/logo.png', 'type' => 'string', 'description' => 'Path to app logo']
        );

        AppSetting::updateOrCreate(
            ['key' => 'break_duration'],
            ['value' => '30', 'type' => 'integer', 'description' => 'Break duration in minutes']
        );

        AppSetting::updateOrCreate(
            ['key' => 'late_minutes'],
            ['value' => '15', 'type' => 'integer', 'description' => 'Allowed late minutes']
        );

        AppSetting::updateOrCreate(
            ['key' => 'half_day_allowed_in_month'],
            ['value' => '2', 'type' => 'integer', 'description' => 'Number of half days allowed in a month']
        );

        AppSetting::updateOrCreate(
            ['key' => 'full_day_allowed_in_month'],
            ['value' => '20', 'type' => 'integer', 'description' => 'Number of full days allowed in a month']
        );
        AppSetting::updateOrCreate(
            ['key' => 'app_timezone'],
            ['value' => 'Asia/Karachi', 'type' => 'string', 'description' => 'Application timezone']
        );

        AppSetting::updateOrCreate(
            ['key' => 'app_logo'],
            ['value' => '/images/logo.png', 'type' => 'string', 'description' => 'Path to app logo']
        );
    }
}
