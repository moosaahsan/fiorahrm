<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ledger of compensatory leave *earned* by working on a public holiday.
 *
 * Only the earning side lives here. Once HR approves a credit it is added to the
 * employee's `cpl` leave balance, and spending it is an ordinary Leave record —
 * so approval, deduction, refund and reporting all reuse the existing flow.
 *
 *   worked a holiday  → compensatory_leaves row (Pending)
 *   HR approves       → leave_balances('cpl') credited
 *   employee takes it → leaves row (leave_type = 'cpl') → balance deducted
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compensatory_leaves')) {
            Schema::create('compensatory_leaves', function (Blueprint $table) {
                $table->id();

                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

                // The attendance that proves the holiday was worked. Nullable so HR
                // can also grant a credit by hand.
                $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();

                // The holiday that was worked, when it came from a configured off day.
                $table->foreignId('company_off_day_id')->nullable()->constrained('company_off_days')->nullOnDelete();

                $table->date('worked_date');
                $table->string('holiday_title')->nullable();

                $table->decimal('days_earned', 5, 2)->default(1.00);

                $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Cancelled'])->default('Pending');

                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();

                // Whether the approved credit has been added to leave_balances yet.
                $table->boolean('is_credited')->default(false);

                // Optional validity window. Null means the credit never expires.
                $table->date('expires_at')->nullable();

                $table->text('notes')->nullable();

                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

                $table->timestamps();

                // One credit per employee per worked date — keeps auto-detection idempotent.
                $table->unique(['employee_id', 'worked_date'], 'cpl_employee_worked_date_unique');
                $table->index(['status', 'worked_date']);
            });
        }

        // ── Configurable CPL policy ──
        $settings = [
            [
                'key' => 'cpl_days_per_holiday',
                'value' => '1',
                'type' => 'string',
                'description' => 'Compensatory leave days earned for working a full public holiday',
            ],
            [
                'key' => 'cpl_auto_approve',
                'value' => '0',
                'type' => 'integer',
                'description' => 'Credit compensatory leave automatically (1) or require HR approval first (0)',
            ],
            [
                'key' => 'cpl_validity_days',
                'value' => '0',
                'type' => 'integer',
                'description' => 'Days an approved compensatory leave stays usable. 0 means it never expires.',
            ],
        ];

        $now = now();

        foreach ($settings as $setting) {
            if (DB::table('app_settings')->where('key', $setting['key'])->exists()) {
                continue;
            }

            DB::table('app_settings')->insert($setting + [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compensatory_leaves');

        DB::table('app_settings')
            ->whereIn('key', ['cpl_days_per_holiday', 'cpl_auto_approve', 'cpl_validity_days'])
            ->delete();
    }
};
