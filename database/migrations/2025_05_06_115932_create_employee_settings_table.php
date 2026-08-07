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
        Schema::create('employee_settings', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->unsignedBigInteger('emp_id'); // Reference to employee
            $table->string('setting_name'); // Name of the setting
            $table->text('setting_value'); // Value for the setting
            $table->unsignedBigInteger('updated_by')->nullable(); // Admin/HR who updated this

            $table->timestamps(); // created_at, updated_at

            // Foreign keys (optional)
            $table->foreign('emp_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_settings');
    }
};
