<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Ensure leave_type is a nullable string (VARCHAR(255)) to match leave_types.slug
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('leave_type', 255)->nullable()->change();
        });

        // Step 2: Update invalid leave_type values to NULL
        $validSlugs = DB::table('leave_types')->pluck('slug')->toArray();
        DB::table('leaves')
            ->whereNotIn('leave_type', $validSlugs)
            ->whereNotNull('leave_type')
            ->update(['leave_type' => null]);

        // Step 3: Add foreign key constraint
        Schema::table('leaves', function (Blueprint $table) {
            $table->foreign('leave_type')
                  ->references('slug')
                  ->on('leave_types')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['leave_type']);
            $table->string('leave_type', 255)->nullable()->change();
        });
    }
};