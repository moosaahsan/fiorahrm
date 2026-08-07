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
        Schema::create('checkins', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->unsignedBigInteger('emp_id'); // Reference to employee
            $table->dateTime('check_in_time'); // Actual check-in time
            $table->enum('status', ['Present', 'Absent']); // Check-in status

            $table->timestamps(); // created_at, updated_at

            // Foreign key constraint (optional)
            $table->foreign('emp_id')->references('id')->on('employees')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};
