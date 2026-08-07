<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'suspended_start_date')) {
                $table->date('suspended_start_date')->nullable()->after('served_notice');
            }
            if (!Schema::hasColumn('employees', 'suspended_end_date')) {
                $table->date('suspended_end_date')->nullable()->after('suspended_start_date');
            }
        });

        Schema::table('employee_exit_records', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_exit_records', 'suspended_start_date')) {
                $table->date('suspended_start_date')->nullable()->after('served_notice');
            }
            if (!Schema::hasColumn('employee_exit_records', 'suspended_end_date')) {
                $table->date('suspended_end_date')->nullable()->after('suspended_start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'suspended_end_date')) {
                $table->dropColumn('suspended_end_date');
            }
            if (Schema::hasColumn('employees', 'suspended_start_date')) {
                $table->dropColumn('suspended_start_date');
            }
        });

        Schema::table('employee_exit_records', function (Blueprint $table) {
            if (Schema::hasColumn('employee_exit_records', 'suspended_end_date')) {
                $table->dropColumn('suspended_end_date');
            }
            if (Schema::hasColumn('employee_exit_records', 'suspended_start_date')) {
                $table->dropColumn('suspended_start_date');
            }
        });
    }
};
