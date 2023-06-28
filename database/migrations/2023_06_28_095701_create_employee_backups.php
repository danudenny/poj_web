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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('job_id')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('shift_type', ['Shift', 'Non Shift']);
            $table->unsignedBigInteger('timesheet_id')->nullable();
            $table->float('duration');
            $table->unsignedBigInteger('asignee_id')->nullable();
            $table->timestamps();

            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            $table->foreign('timesheet_id')->references('id')->on('employee_timesheet')->onDelete('cascade');
            $table->foreign('asignee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_backups');
    }
};
