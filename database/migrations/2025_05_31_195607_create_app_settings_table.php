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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id()->comment('Primary key');
            $table->string('key', 100)->unique()->comment('Unique setting key');
            $table->text('value')->comment('Value for the setting');
            $table->string('type', 50)->nullable()->comment('Type of the value (string, number, json, boolean)');
            $table->text('description')->nullable()->comment('Description of the setting');
          
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
