<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->integer('grace_period')->default(0)->after('crosses_midnight');
            $table->time('halfday_mark')->nullable()->after('grace_period');
            $table->time('late_mark')->nullable()->after('halfday_mark');
            $table->boolean('is_active')->default(true)->after('late_mark');
        });
    }

    public function down()
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn([
                'grace_period',
                'halfday_mark',
                'late_mark',
                'is_active',
            ]);
        });
    }
};
