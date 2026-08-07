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
            if (!Schema::hasColumn('employees', 'resigned_by')) {
                $table->foreignId('resigned_by')->nullable()->constrained('users')->onDelete('set null')->after('served_notice');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'resigned_by')) {
                $table->dropForeign(['resigned_by']);
                $table->dropColumn('resigned_by');
            }
        });
    }
};
