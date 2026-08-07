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
            $table->dropForeign(['first_interviewer_id']);
            $table->dropForeign(['second_interviewer_id']);
            $table->dropColumn(['first_interviewer_id', 'second_interviewer_id']);
            
            $table->string('first_interviewer')->nullable()->after('cv_path');
            $table->string('second_interviewer')->nullable()->after('first_interviewer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropColumn(['first_interviewer', 'second_interviewer']);
            
            $table->foreignId('first_interviewer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('second_interviewer_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }
};
