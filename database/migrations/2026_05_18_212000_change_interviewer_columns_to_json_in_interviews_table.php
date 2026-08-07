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
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropColumn(['first_interviewer', 'second_interviewer']);
            $table->json('interviewers')->nullable()->after('cv_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropColumn('interviewers');
            $table->string('first_interviewer')->nullable()->after('cv_path');
            $table->string('second_interviewer')->nullable()->after('first_interviewer');
        });
    }
};
