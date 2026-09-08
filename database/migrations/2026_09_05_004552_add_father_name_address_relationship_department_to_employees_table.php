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
            $table->string('father_name')->nullable()->after('name');
            $table->text('address')->nullable()->after('emergency_no');
            $table->string('emergency_relationship')->nullable()->after('address');
            $table->foreignId('department_id')->nullable()->after('team_id')->constrained('departments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['father_name', 'address', 'emergency_relationship']);
        });
    }
};
