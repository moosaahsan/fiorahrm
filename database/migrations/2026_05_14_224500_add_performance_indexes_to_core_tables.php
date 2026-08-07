<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Attendances: Composite index for most common query patterns (log views, daily reports)
        Schema::table('attendances', function (Blueprint $table) {
            $table->index(['emp_id', 'shift_date'], 'idx_attendances_emp_date');
            $table->index(['branch_id', 'shift_date'], 'idx_attendances_branch_date');
        });

        // 2. Late Arrivals & Half Days: Grouped queries for dashboard & payroll
        Schema::table('late_arrivals', function (Blueprint $table) {
            $table->index(['emp_id', 'date'], 'idx_late_arrivals_emp_date');
        });

        Schema::table('half_days', function (Blueprint $table) {
            $table->index(['emp_id', 'date'], 'idx_half_days_emp_date');
        });

        // 3. Employee Breaks: Heavy use in net minutes calculation
        Schema::table('employee_breaks', function (Blueprint $table) {
            $table->index(['emp_id', 'shift_date'], 'idx_breaks_emp_date');
        });

        // 4. Leaves: Filtering by date ranges for attendance status & payroll
        Schema::table('leaves', function (Blueprint $table) {
            $table->index(['employee_id', 'start_date', 'end_date'], 'idx_leaves_emp_range');
        });

        // 5. Employee Shifts: Resolving current/historical shift assignments
        Schema::table('employee_shifts', function (Blueprint $table) {
            $table->index(['emp_id', 'assigned_at'], 'idx_emp_shifts_emp_assigned');
        });
        
        // 6. App Response: Critical for real-time tracking dashboard
        Schema::table('app_response', function (Blueprint $table) {
            $table->index(['emp_id', 'shift_date', 'response_update'], 'idx_app_res_emp_date_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', fn(Blueprint $table) => $table->dropIndex('idx_attendances_emp_date'));
        Schema::table('attendances', fn(Blueprint $table) => $table->dropIndex('idx_attendances_branch_date'));
        Schema::table('late_arrivals', fn(Blueprint $table) => $table->dropIndex('idx_late_arrivals_emp_date'));
        Schema::table('half_days', fn(Blueprint $table) => $table->dropIndex('idx_half_days_emp_date'));
        Schema::table('employee_breaks', fn(Blueprint $table) => $table->dropIndex('idx_breaks_emp_date'));
        Schema::table('leaves', fn(Blueprint $table) => $table->dropIndex('idx_leaves_emp_range'));
        Schema::table('employee_shifts', fn(Blueprint $table) => $table->dropIndex('idx_emp_shifts_emp_assigned'));
        Schema::table('app_response', fn(Blueprint $table) => $table->dropIndex('idx_app_res_emp_date_update'));
    }
};
