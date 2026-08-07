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
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->decimal('allocated', 5, 2)->change();
            $table->decimal('used', 5, 2)->change();
            $table->decimal('remaining', 5, 2)->change();
        });
    }

    public function down()
    {
        Schema::table('leave_balances', function (Blueprint $table) {
            $table->integer('allocated')->change();
            $table->integer('used')->change();
            $table->integer('remaining')->change();
        });
    }
};
