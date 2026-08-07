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
            $table->string('email')->nullable()->after('phone');
            $table->string('experience')->nullable()->after('email');
            $table->string('reference')->nullable()->after('experience');
            $table->string('source')->nullable()->after('reference'); // e.g., How they found us
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            $table->dropColumn(['email', 'experience', 'reference', 'source']);
        });
    }
};
