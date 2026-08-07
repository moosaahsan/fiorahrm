<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('break_duration')
                ->default(0)
                ->comment('Break duration in minutes');

            $table->integer('break_allowed_in_half_day')
                ->default(30)
                ->comment('Break allowed in half day in minutes');

            $table->integer('number_full_days_allowed_in_month')
                ->default(0)
                ->comment('Number of full days allowed in a month');

            $table->integer('number_half_days_allowed_in_month')
                ->default(0)
                ->comment('Number of half days allowed in a month');

            $table->integer('late_minutes_margin')
                ->default(0)
                ->comment('Late minutes margin');

            $table->integer('leaves_allowed_in_year')
                ->default(16)
                ->comment('Leaves allowed in a year');

            $table->integer('idle_time_allowed')
                ->default(0)
                ->comment('Allowed idle time in minutes (inactive on tracking app)');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'break_duration',
                'break_allowed_in_half_day',
                'number_full_days_allowed_in_month',
                'number_half_days_allowed_in_month',
                'late_minutes_margin',
                'leaves_allowed_in_year',
                'idle_time_allowed'
            ]);
        });
    }
};

