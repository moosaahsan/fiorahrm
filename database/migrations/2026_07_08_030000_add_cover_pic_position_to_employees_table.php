<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'cover_pic_position')) {
                $table->string('cover_pic_position', 64)->nullable()->after('cover_pic');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'cover_pic_position')) {
                $table->dropColumn('cover_pic_position');
            }
        });
    }
};
