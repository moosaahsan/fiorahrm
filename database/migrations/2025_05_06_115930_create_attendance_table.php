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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id(); // Primary key for the attendance record

            // Ensure emp_id references the correct type
            $table->foreignId('emp_id')->constrained('employees')->onDelete('cascade'); // Correct foreign key to employees table

            // Reference to shifts (nullable, can be null)
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');

            $table->dateTime('check_in')->nullable(); // Check-in datetime
            $table->dateTime('check_out')->nullable(); // Check-out datetime

            $table->integer('late_duration')->default(0); // Late time in minutes
            $table->boolean('manual')->default(false); // Manual entry flag

            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
