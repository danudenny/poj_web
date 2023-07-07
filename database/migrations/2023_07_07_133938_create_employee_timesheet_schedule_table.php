<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('year');
            $table->string('month');
            $table->timestamps();
        });

        Schema::create('employee_timesheet_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('timesheet_id');
            $table->unsignedBigInteger('period_id');
            $table->unsignedBigInteger('employee_id');
            $table->integer('date');
            $table->timestamps();

            $table->foreign('timesheet_id')->references('id')->on('employee_timesheet')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('periods')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('periods');
        Schema::dropIfExists('employee_timesheet_schedule');
    }
};
