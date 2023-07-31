<?php

use App\Models\Backup;
use App\Models\Overtime;
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
        Schema::create('backup_approvals', function (Blueprint $table) {
            $table->id();
            $table->integer('priority');
            $table->integer('backup_id');
            $table->integer('user_id');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('overtime_approvals', function (Blueprint $table) {
            $table->id();
            $table->integer('priority');
            $table->integer('overtime_id');
            $table->integer('user_id');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('event_approvals', function (Blueprint $table) {
            $table->id();
            $table->integer('priority');
            $table->integer('overtime_id');
            $table->integer('user_id');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('backups', function(Blueprint $table) {
            if (!Schema::hasColumn('backups', 'request_type')) {
                $table->string('request_type')->default(Backup::RequestTypeAssignment);
            }

            if (!Schema::hasColumn('backups', 'source_unit_relation_id')) {
                $table->integer('source_unit_relation_id')->nullable();
            }
        });

        Schema::table('overtimes', function(Blueprint $table) {
            if (!Schema::hasColumn('overtimes', 'request_type')) {
                $table->string('request_type')->default(Overtime::RequestTypeAssignment);
            }
        });

        Schema::table('employee_timesheet_schedules', function (Blueprint $table) {
            if (!Schema::hasColumns('employee_timesheet_schedules', ['start_time', 'end_time', 'early_buffer', 'late_buffer', 'timezone', 'latitude', 'longitude'])) {
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();
                $table->integer('early_buffer')->nullable();
                $table->integer('late_buffer')->nullable();
                $table->string('timezone')->nullable();
                $table->string('latitude')->nullable();
                $table->string('longitude')->nullable();
            }

            if (!Schema::hasColumns('employee_timesheet_schedules', ['check_in_time', 'check_out_time', 'check_in_latitude', 'check_in_longitude', 'check_out_latitude', 'check_out_longitude', 'check_in_timezone', 'check_out_timezone'])) {
                $table->timestamp('check_in_time')->nullable();
                $table->timestamp('check_out_time')->nullable();
                $table->string('check_in_latitude')->nullable();
                $table->string('check_in_longitude')->nullable();
                $table->string('check_out_latitude')->nullable();
                $table->string('check_out_longitude')->nullable();
                $table->string('check_in_timezone')->nullable();
                $table->string('check_out_timezone')->nullable();
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
        Schema::dropIfExists('backup_approvals');
        Schema::dropIfExists('overtime_approvals');
        Schema::dropIfExists('event_approvals');
    }
};
