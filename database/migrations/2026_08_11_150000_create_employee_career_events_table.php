<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Career milestones on an employee's record — salary increments, promotions and
 * confirmation off probation.
 *
 * One table for all three because they answer the same question ("what changed,
 * when, and from what to what"), and because "last increment" / "last promotion"
 * then reduce to a single ordered lookup. Adding a transfer or a demotion later
 * means one more enum value, not another table.
 *
 * Recording an event also applies it: an increment updates the employee's
 * salary, a promotion updates their designation, a confirmation stamps
 * employees.confirmed_at. The row stays as the audit trail of what it replaced.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_career_events')) {
            Schema::create('employee_career_events', function (Blueprint $table) {
                $table->id();

                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

                $table->enum('type', ['increment', 'promotion', 'confirmation']);
                $table->date('effective_date');

                // Filled for increments.
                $table->decimal('previous_salary', 12, 2)->nullable();
                $table->decimal('new_salary', 12, 2)->nullable();

                // Filled for promotions.
                $table->string('previous_position')->nullable();
                $table->string('new_position')->nullable();

                $table->text('notes')->nullable();

                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

                $table->timestamps();

                $table->index(['employee_id', 'type', 'effective_date']);
            });
        }

        if (! Schema::hasColumn('employees', 'confirmed_at')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->date('confirmed_at')
                    ->nullable()
                    ->after('probation')
                    ->comment('Date HR confirmed the employee off probation');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_career_events');

        if (Schema::hasColumn('employees', 'confirmed_at')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('confirmed_at');
            });
        }
    }
};
