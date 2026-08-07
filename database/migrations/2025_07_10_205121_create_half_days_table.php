<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHalfDaysTable extends Migration
{
    public function up()
    {
        Schema::create('half_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emp_id');
            $table->unsignedBigInteger('attendance_id');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->date('date');
            $table->string('reason')->nullable(); // e.g. 'late', 'short_hours'
            $table->enum('source', ['auto', 'manual'])->default('auto');
            $table->unsignedBigInteger('created_by')->nullable(); // HR/admin who manually added
            $table->timestamps();

            $table->foreign('emp_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('half_days');
    }
}

