<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            // First rename emp_id to employee_id
            $table->renameColumn('emp_id', 'employee_id');
        });

        // Then in a separate Schema call, add shift_id AFTER employee_id
        Schema::table('leaves', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('employee_id');
        });
    }


    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('shift_id');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->renameColumn('employee_id', 'emp_id');
        });
    }

};

