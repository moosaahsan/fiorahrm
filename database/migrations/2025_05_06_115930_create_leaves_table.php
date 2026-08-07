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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->foreignId('emp_id')->constrained('employees')->onDelete('cascade'); // Employee reference

            $table->enum('leave_type', ['Full', 'Half']); // Type of leave
            $table->date('start_date'); // Leave start
            $table->date('end_date'); // Leave end

            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending'); // Leave status
            $table->text('reason')->nullable(); // Optional reason for leave
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // HR/Manager approval (optional)

            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
