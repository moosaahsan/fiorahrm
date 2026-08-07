<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('performance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->decimal('value', 8, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('performance_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->unsignedInteger('month');
            $table->unsignedInteger('year');
            $table->decimal('attendance_score', 5, 2)->default(0);
            $table->decimal('leave_score', 5, 2)->default(0);
            $table->decimal('break_score', 5, 2)->default(0);
            $table->decimal('late_score', 5, 2)->default(0);
            $table->decimal('dress_code_score', 5, 2)->default(0); 
            $table->decimal('work_performance_score', 5, 2)->default(0);
            $table->decimal('behavior_score', 5, 2)->default(0);
            $table->text('comments')->nullable();
            $table->decimal('total_score', 5, 2)->default(0);
            $table->string('status')->default('draft'); // draft, submitted, approved
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['employee_id', 'month', 'year']);
        });

        Schema::create('employee_of_the_month', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('month');
            $table->unsignedInteger('year');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('performance_evaluation_id')->constrained('performance_evaluations')->onDelete('cascade');
            $table->text('bio_comments')->nullable();
            $table->timestamps();

            $table->unique(['month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_of_the_month');
        Schema::dropIfExists('performance_evaluations');
        Schema::dropIfExists('performance_settings');
    }
};
