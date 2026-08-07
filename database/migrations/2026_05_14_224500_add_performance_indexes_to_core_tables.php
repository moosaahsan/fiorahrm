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
            if (! $this->indexExists('attendances', 'idx_attendances_emp_date')) {
                $table->index(['emp_id', 'shift_date'], 'idx_attendances_emp_date');
            }
            if (! $this->indexExists('attendances', 'idx_attendances_branch_date')) {
                $table->index(['branch_id', 'shift_date'], 'idx_attendances_branch_date');
            }
        });

        // 2. Late Arrivals & Half Days: Grouped queries for dashboard & payroll
        Schema::table('late_arrivals', function (Blueprint $table) {
            if (! $this->indexExists('late_arrivals', 'idx_late_arrivals_emp_date')) {
                $table->index(['emp_id', 'date'], 'idx_late_arrivals_emp_date');
            }
        });

        Schema::table('half_days', function (Blueprint $table) {
            if (! $this->indexExists('half_days', 'idx_half_days_emp_date')) {
                $table->index(['emp_id', 'date'], 'idx_half_days_emp_date');
            }
        });

        // 3. Employee Breaks: Heavy use in net minutes calculation
        Schema::table('employee_breaks', function (Blueprint $table) {
            if (! $this->indexExists('employee_breaks', 'idx_breaks_emp_date')) {
                $table->index(['emp_id', 'shift_date'], 'idx_breaks_emp_date');
            }
        });

        // 4. Leaves: Filtering by date ranges for attendance status & payroll
        Schema::table('leaves', function (Blueprint $table) {
            if (! $this->indexExists('leaves', 'idx_leaves_emp_range')) {
                $table->index(['employee_id', 'start_date', 'end_date'], 'idx_leaves_emp_range');
            }
        });

        // 5. Employee Shifts: Resolving current/historical shift assignments
        Schema::table('employee_shifts', function (Blueprint $table) {
            if (! $this->indexExists('employee_shifts', 'idx_emp_shifts_emp_assigned')) {
                $table->index(['emp_id', 'assigned_at'], 'idx_emp_shifts_emp_assigned');
            }
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
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($idx) => ($idx->name ?? null) === $indexName);
        }

        $database = $connection->getDatabaseName();
        $result = $connection->select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }
};
