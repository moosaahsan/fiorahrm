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
        Schema::table('leave_summaries', function (Blueprint $table) {
            $table->decimal('policy_adjustments', 8, 2)->default(0)->after('manual_adjustments');
        });
    }

    public function down(): void
    {
        Schema::table('leave_summaries', function (Blueprint $table) {
            $table->dropColumn('policy_adjustments');
        });
    }
};
