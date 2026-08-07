<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('salary_structures', function (Blueprint $blueprint) {
            $blueprint->longText('deductions')->nullable()->after('allowances');
            $blueprint->longText('config')->nullable()->after('deductions');
            $blueprint->date('effective_date')->nullable()->after('config');
            $blueprint->string('employee_type')->nullable()->after('effective_date');
        });
    }

    public function down()
    {
        Schema::table('salary_structures', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['deductions', 'config', 'effective_date', 'employee_type']);
        });
    }
};
