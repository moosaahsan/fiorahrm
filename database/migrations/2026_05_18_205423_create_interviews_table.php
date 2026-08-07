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
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cnic')->index();
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('cv_path')->nullable();
            $table->foreignId('first_interviewer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('second_interviewer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('remarks')->nullable();
            $table->string('status')->default('Pending'); // e.g. Pending, 1st Round, 2nd Round, Selected, Rejected, Onboarded
            $table->dateTime('interview_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
