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
        Schema::table('employees', function (Blueprint $table) {
            $table->index('joining_date', 'idx_employees_joining');
            $table->index('resign_date', 'idx_employees_resign');
        });

        Schema::table('company_off_days', function (Blueprint $table) {
            $table->index(['start_date', 'end_date'], 'idx_offdays_range');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_employees_joining');
            $table->dropIndex('idx_employees_resign');
        });

        Schema::table('company_off_days', function (Blueprint $table) {
            $table->dropIndex('idx_offdays_range');
        });
    }
};
