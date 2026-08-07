<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->integer('friday_break')->nullable()->default(75)->comment('Break duration on Friday (in minutes)');
            $table->integer('otherday_break')->nullable()->default(60)->comment('Break duration on other days (in minutes)');
            $table->integer('halfday_break')->nullable()->default(30)->comment('Break duration if employee is on half-day');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            //
        });
    }
};
