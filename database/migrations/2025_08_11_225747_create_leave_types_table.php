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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('name')->comment('Human-readable name of the leave type (e.g., Casual Leave)');
            $table->string('slug')->unique()->comment('Unique identifier referenced by leaves table (e.g., casual)');
            $table->text('description')->nullable()->comment('Optional description of the leave type');
            $table->unsignedSmallInteger('max_days')->default(0)->comment('Maximum days allowed per year/period');
            $table->boolean('is_paid')->default(true)->comment('Indicates if the leave is paid');
            $table->boolean('carry_forward')->default(false)->comment('Indicates if unused leave can be carried forward');
            $table->unsignedSmallInteger('max_carry_forward_days')->default(0)->comment('Maximum days that can be carried forward');
            $table->boolean('requires_approval')->default(true)->comment('Indicates if approval is required');
            $table->boolean('is_active')->default(true)->comment('Indicates if the leave type is active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};