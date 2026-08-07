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
        Schema::table('interviews', function (Blueprint $table) {
            $table->unsignedBigInteger('job_posting_id')->nullable()->after('id');
            $table->string('category')->default('BPO')->after('job_posting_id'); // BPO, Billing
            $table->string('position_applied')->nullable()->after('category');
            
            $table->foreign('job_posting_id')->references('id')->on('job_postings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            //
        });
    }
};
