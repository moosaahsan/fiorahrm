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
        Schema::create('leave_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emp_id')->constrained('employees')->onDelete('cascade');
            $table->year('year');
            $table->integer('total_leaves')->default(0);
            $table->decimal('used_leaves', 5, 2)->default(0); // 0.5 possible for half
            $table->integer('rejected_leaves')->default(0);
            $table->integer('half_leaves')->default(0);
            $table->integer('carried_forward')->nullable();
            $table->integer('manual_adjustments')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_summaries');
    }
};
