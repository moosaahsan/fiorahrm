<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hotel leave policy baseline.
 *
 * Seeds the configurable settings and leave types the hotel HRM runs on.
 * Everything here is data, not schema — the values stay editable from
 * Admin → Settings and the leave_types table, so the policy can change
 * later without another migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // ── Configurable policy settings (rendered automatically by Admin → Settings) ──
        $settings = [
            [
                'key' => 'leave_eligibility_months',
                'value' => '6',
                'type' => 'integer',
                'description' => 'Months an employee must complete after joining before leave entitlement unlocks',
            ],
            [
                'key' => 'leave_carry_forward_enabled',
                'value' => '0',
                'type' => 'integer',
                'description' => 'Carry unused leave into next year (0 = no carry forward, balance is cashed out instead)',
            ],
        ];

        foreach ($settings as $setting) {
            $exists = DB::table('app_settings')->where('key', $setting['key'])->exists();

            if ($exists) {
                // Keep whatever value the admin has already set — only refresh the metadata.
                DB::table('app_settings')->where('key', $setting['key'])->update([
                    'type' => $setting['type'],
                    'description' => $setting['description'],
                    'updated_at' => $now,
                ]);
                continue;
            }

            DB::table('app_settings')->insert($setting + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ── Hotel leave types ──
        // max_days is the annual entitlement. CPL carries 0 because it is earned
        // by working a public holiday, never allocated up front.
        $leaveTypes = [
            ['slug' => 'sick',      'name' => 'Sick Leave',         'description' => 'Leave for medical reasons',            'max_days' => 10],
            ['slug' => 'casual',    'name' => 'Casual Leave',       'description' => 'Leave for personal reasons',           'max_days' => 10],
            ['slug' => 'annual',    'name' => 'Annual Leave',       'description' => 'Annual vacation entitlement',          'max_days' => 15],
            ['slug' => 'cpl',       'name' => 'Compensatory Leave', 'description' => 'Earned by working on a public holiday','max_days' => 0],
            ['slug' => 'maternity', 'name' => 'Maternity Leave',    'description' => 'Leave for maternity',                  'max_days' => 90],
        ];

        foreach ($leaveTypes as $type) {
            $payload = $type + [
                'is_paid' => 1,
                // No carry forward anywhere — unused balance is cashed out at year end.
                'carry_forward' => 0,
                'max_carry_forward_days' => 0,
                'requires_approval' => 1,
                'is_active' => 1,
                'updated_at' => $now,
            ];

            $existing = DB::table('leave_types')->where('slug', $type['slug'])->first();

            if ($existing) {
                DB::table('leave_types')->where('slug', $type['slug'])->update($payload);
            } else {
                DB::table('leave_types')->insert($payload + ['created_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('app_settings')
            ->whereIn('key', ['leave_eligibility_months', 'leave_carry_forward_enabled'])
            ->delete();

        DB::table('leave_types')->where('slug', 'cpl')->delete();
    }
};
