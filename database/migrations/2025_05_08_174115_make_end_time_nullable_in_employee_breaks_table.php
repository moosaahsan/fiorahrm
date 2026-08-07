<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('employee_breaks', function (Blueprint $table) {
            $table->dateTime('end_time')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employee_breaks', function (Blueprint $table) {
            $table->dateTime('end_time')->nullable(false)->change();
        });
    }
};
