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
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); // Primary key for the employee record
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Reference to users table

            $table->string('name'); // Full name of the employee
            $table->string('position')->nullable(); // Job position of the employee
            $table->date('joining_date')->nullable(); // Joining date of the employee
            $table->string('probation')->nullable(); // Probation status of the employee

            $table->string('email')->nullable(); // Email address (can be nullable or duplicate)
            $table->string('contact_no')->nullable(); // Contact number
            $table->string('emergency_no')->nullable(); // Emergency contact number

            $table->string('pin_code')->nullable(); // PIN code for clock-in/out
            $table->text('permissions')->nullable(); // Permissions for the employee (JSON or comma-separated)

            $table->timestamp('email_verified_at')->nullable(); // Email verification timestamp
            $table->rememberToken(); // Token to remember the employee's session

            $table->tinyInteger('tracking')->default(1); // Tracking status (1 = enabled, 0 = disabled)
            $table->date('resign_date')->nullable(); // Resignation date (if applicable)

            $table->tinyInteger('status')->default(1); // Status of the employee (1 = Active, 0 = Inactive)
            $table->string('gender')->nullable(); // Gender of the employee
            $table->date('dob')->nullable(); // Date of birth of the employee
            $table->string('profile_pic')->nullable(); // URL or path of the employee's profile picture

            $table->timestamps(); // created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
