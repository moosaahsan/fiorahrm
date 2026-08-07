<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('performance_evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('performance_evaluations', 'auto_attendance_score')) {
                $table->renameColumn('auto_attendance_score', 'attendance_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('performance_evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('performance_evaluations', 'attendance_score')) {
                $table->renameColumn('attendance_score', 'auto_attendance_score');
            }
        });
    }
};
