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
        Schema::table('employee_breaks', function (Blueprint $table) {
            $table->date('shift_date')->nullable()->after('emp_id');
        });
    }

    public function down()
    {
        Schema::table('employee_breaks', function (Blueprint $table) {
            $table->dropColumn('shift_date');
        });
    }

};
