<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Year-end encashment of unused leave.
 *
 * Nothing carries forward — at the end of the year HR cashes out whatever is
 * left. There is no rate formula on purpose: HR enters the payable amount, and
 * it is added to the chosen month's payroll as an earnings line.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leave_cashouts')) {
            return;
        }

        Schema::create('leave_cashouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            // The leave year being cashed out, and the leave type slug.
            $table->year('year');
            $table->string('leave_type');

            $table->decimal('days', 6, 2);
            $table->decimal('amount', 15, 2)->default(0);

            $table->enum('status', ['Pending', 'Paid', 'Cancelled'])->default('Pending');

            // Which payroll run should carry the payout.
            $table->unsignedTinyInteger('payroll_month');
            $table->year('payroll_year');

            $table->foreignId('payroll_item_id')->nullable()->constrained('payroll_items')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();

            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->timestamps();

            // One cashout per employee, per leave type, per leave year.
            $table->unique(['employee_id', 'year', 'leave_type'], 'leave_cashout_employee_year_type_unique');
            $table->index(['payroll_year', 'payroll_month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_cashouts');
    }
};
