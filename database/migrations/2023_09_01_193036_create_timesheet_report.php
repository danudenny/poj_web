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
        Schema::create('timesheet_reports', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('unit_relation_id');
            $table->timestamp('last_sync');
            $table->string('last_sync_by');
            $table->string('status');
            $table->string('created_by');
            $table->timestamps();
        });

        Schema::create('timesheet_report_details', function (Blueprint $table) {
            $table->id();
            $table->integer('timesheet_report_id');
            $table->integer('employee_id');
            $table->integer('total_work_day')->default(0);
            $table->integer('attendance_days')->default(0);
            $table->integer('work_from_home_days')->default(0);
            $table->integer('total_work_weekdays')->default(0);
            $table->integer('total_work_day_off')->default(0);
            $table->integer('total_work_national_holiday')->default(0);
            $table->integer('total_leave')->default(0);
            $table->integer('total_absent')->default(0);
            $table->float('total_overtime_first')->default(0);
            $table->float('total_overtime_second')->default(0);
            $table->float('total_overtime_public_holiday_first')->default(0);
            $table->float('total_overtime_public_holiday_second')->default(0);
            $table->float('total_overtime_public_holiday_third')->default(0);
            $table->float('total_overtime_day_off_first')->default(0);
            $table->float('total_overtime_day_off_second')->default(0);
            $table->float('total_overtime_day_off_third')->default(0);
            $table->float('total_backup')->default(0);
            $table->integer('total_late_15')->default(0);
            $table->integer('total_late_30')->default(0);
            $table->integer('total_late_45')->default(0);
            $table->integer('total_late_60')->default(0);
            $table->integer('total_late_75')->default(0);
            $table->integer('total_late_90')->default(0);
            $table->integer('total_late_105')->default(0);
            $table->integer('total_late_120')->default(0);
            $table->integer('early_check_out_15')->default(0);
            $table->integer('early_check_out_30')->default(0);
            $table->integer('early_check_out_45')->default(0);
            $table->integer('early_check_out_60')->default(0);
            $table->integer('early_check_out_75')->default(0);
            $table->integer('early_check_out_90')->default(0);
            $table->integer('early_check_out_105')->default(0);
            $table->integer('early_check_out_120')->default(0);
            $table->integer('total_extended_day_off')->default(0);
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
        Schema::dropIfExists('timesheet_reports');
        Schema::dropIfExists('timesheet_report_details');
    }
};
