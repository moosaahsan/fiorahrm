<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Makes the weekly off day configurable instead of assuming Sat/Sun.
 *
 * A hotel operates every day, so the default is empty — only dates configured
 * under Holidays are off. Setting this to e.g. "0" (Sunday) or "0,6" restores a
 * fixed weekend without touching code.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('app_settings')->where('key', 'weekly_off_days')->exists()) {
            return;
        }

        DB::table('app_settings')->insert([
            'key' => 'weekly_off_days',
            'value' => '',
            'type' => 'string',
            'description' => 'Recurring weekly off days as day numbers (0=Sunday … 6=Saturday), comma separated. Leave empty for a 7-day operation where only configured holidays are off.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('app_settings')->where('key', 'weekly_off_days')->delete();
    }
};
