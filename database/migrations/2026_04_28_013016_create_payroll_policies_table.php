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
        Schema::create('payroll_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_type'); // 'Global', 'Team', 'Individual'
            $table->unsignedBigInteger('model_id')->nullable(); // Team ID or Employee ID
            $table->json('late_policy')->nullable(); // e.g. {"3_lates": "0.5_day", "grace_period": 15}
            $table->json('break_policy')->nullable(); // e.g. {"max_minutes": 60, "deduct_over": true}
            $table->json('leave_policy')->nullable(); // e.g. {"unpaid_leave": "pro_rata"}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_policies');
    }
};
