<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_punch_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device', 10); // IN or OUT
            $table->string('device_user_id');
            $table->string('device_user_name')->nullable(); // name stored on the device
            $table->dateTime('scan_time'); // device clock, app timezone
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('employee_name')->nullable();
            $table->boolean('ok')->default(false);
            $table->string('result', 500)->nullable();
            $table->string('source', 20)->default('live'); // live or catch-up
            $table->timestamps();

            // One row per physical scan, however many times the bridge re-sends it.
            $table->unique(['device', 'device_user_id', 'scan_time'], 'device_punch_scan_unique');
            $table->index('scan_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_punch_logs');
    }
};
