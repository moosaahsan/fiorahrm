<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('company_off_days', function (Blueprint $table) {
            $table->id();
            $table->date('off_date')->unique(); // Only used for national holidays
            $table->enum('type', ['Weekend', 'Holiday'])->default('Holiday');
            $table->string('note')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_off_days');
    }
};
