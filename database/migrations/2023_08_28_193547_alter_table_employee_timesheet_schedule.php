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
        Schema::table('employee_timesheet_schedules', function (Blueprint $table) {
            if (!Schema::hasColumns('employee_timesheet_schedules', ['start_time_timesheet', 'end_time_timesheet'])) {
                $table->string('start_time_timesheet')->nullable();
                $table->string('end_time_timesheet')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
