<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_off_days', function (Blueprint $table) {

            if (Schema::hasColumn('company_off_days', 'off_date')) {
                // Drop unique index first to prevent SQLite constraint errors
                try {
                    $table->dropUnique('company_off_days_off_date_unique');
                } catch (\Exception $e) {
                    // Ignore if it doesn't exist
                }
                $table->dropColumn('off_date');
            }

            if (!Schema::hasColumn('company_off_days', 'start_date')) {
                $table->date('start_date')->after('id');
            }

            if (!Schema::hasColumn('company_off_days', 'end_date')) {
                $table->date('end_date')->after('start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_off_days', function (Blueprint $table) {

            if (Schema::hasColumn('company_off_days', 'start_date')) {
                $table->dropColumn('start_date');
            }

            if (Schema::hasColumn('company_off_days', 'end_date')) {
                $table->dropColumn('end_date');
            }

            if (!Schema::hasColumn('company_off_days', 'off_date')) {
                $table->date('off_date')->unique();
            }
        });
    }
};
