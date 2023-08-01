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
        Schema::table('backup_employee_times', function(Blueprint $table) {
            if (!Schema::hasColumn('backup_employee_times', 'employee_attendance_id')) {
                $table->integer('employee_attendance_id')->nullable();
            }
        });

        Schema::table('overtime_employees', function(Blueprint $table) {
            if (!Schema::hasColumn('overtime_employees', 'employee_attendance_id')) {
                $table->integer('employee_attendance_id')->nullable();
            }
        });

        Schema::table('employee_events', function(Blueprint $table) {
            if (!Schema::hasColumn('employee_events', 'employee_attendance_id')) {
                $table->integer('employee_attendance_id')->nullable();
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
