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
        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id(); // Unique ID for each shift entry
            $table->foreignId('emp_id')->constrained('employees')->onDelete('cascade'); // Foreign key to employees table
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade'); // Foreign key to shifts table
            $table->timestamp('assigned_at'); // When the shift was assigned
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_shifts');
    }
};
