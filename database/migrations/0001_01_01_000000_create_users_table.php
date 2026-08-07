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
        // Users table creation
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Primary key for user
            $table->string('name'); // User's full name
            $table->string('email')->unique(); // Unique email
            $table->timestamp('email_verified_at')->nullable(); // Email verification timestamp
            $table->string('password'); // User's password
            $table->boolean('is_admin')->default(false); // Add this line
            $table->rememberToken(); // Remember token for session persistence
            $table->timestamps(); // Timestamps for created_at and updated_at
        });

        // Password reset tokens table creation
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); // Email is the primary key
            $table->string('token'); // Token for password reset
            $table->timestamp('created_at')->nullable(); // Timestamp when the token was created
        });

        // Sessions table creation
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); // Session ID
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Foreign key to users table
            $table->string('ip_address', 45)->nullable(); // IP address of the session
            $table->text('user_agent')->nullable(); // User agent string
            $table->longText('payload'); // Session payload data
            $table->integer('last_activity')->index(); // Timestamp of last activity
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dropping the tables in reverse order of creation
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
