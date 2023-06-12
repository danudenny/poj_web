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
        Schema::create('employee_timesheet', function (Blueprint $table) {
            $table->id()->index();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->timestamps();
        });

        Schema::create('employee_details', function (Blueprint $table) {
            $table->id()->index();
            $table->integer('employee_id');
            $table->integer('employee_timesheet_id');
            $table->enum('ettendance_type', ['onsite', 'offsite', 'other', 'remote', 'hybrid']);
            $table->timestamps();
        });

        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->id()->index();
            $table->dateTime('real_check_in');
            $table->dateTime('real_check_out');
            $table->enum('ettendance_type', ['onsite', 'offsite', 'other', 'remote', 'hybrid']);
            $table->integer('duration');
            $table->integer('employee_id');
            $table->timestamps();
        });

        Schema::create('employee_attendance_histories', function (Blueprint $table) {
            $table->id()->index();
            $table->integer('employee_id');
            $table->integer('employee_attendances_id');
            $table->enum('status', ['pending', 'reviewed', 'approved', 'completed', 'rejected', 'corrected']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_timesheet');
        Schema::dropIfExists('employee_details');
        Schema::dropIfExists('employee_attendances');
    }
};
