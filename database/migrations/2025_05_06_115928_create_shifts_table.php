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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id(); // Primary key

            $table->string('shift_name'); // Name of the shift (e.g., Morning, Evening, Night)
            $table->time('start_time'); // Shift start time
            $table->time('end_time'); // Shift end time
            $table->boolean('crosses_midnight')->default(false); // Optional: whether shift spans across midnight

            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
