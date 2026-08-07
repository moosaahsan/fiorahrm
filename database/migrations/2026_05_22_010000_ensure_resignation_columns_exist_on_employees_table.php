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
            if (!Schema::hasColumn('employees', 'resign_reason')) {
                $table->text('resign_reason')->nullable()->after('resign_date');
            }
            if (!Schema::hasColumn('employees', 'exit_type')) {
                $table->string('exit_type')->nullable()->default('resigned')->after('resign_reason');
            }
            if (!Schema::hasColumn('employees', 'served_notice')) {
                $table->boolean('served_notice')->nullable()->after('exit_type');
            }
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
            if (Schema::hasColumn('employees', 'served_notice')) {
                $table->dropColumn('served_notice');
            }
            if (Schema::hasColumn('employees', 'exit_type')) {
                $table->dropColumn('exit_type');
            }
            if (Schema::hasColumn('employees', 'resign_reason')) {
                $table->dropColumn('resign_reason');
            }
        });
    }
};
