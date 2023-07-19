<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('backups', function (Blueprint $table) {
            if (Schema::hasColumns('backups', ['timesheet_id', 'assignee_id'])) {
                $table->dropColumn('timesheet_id');
                $table->dropColumn('assignee_id');

                $table->dropForeign('backups_job_id_foreign');
                $table->dropForeign('backups_unit_id_foreign');
            }

            if (!Schema::hasColumns('backups', ['status'])) {
                $table->enum('status', ['assigned', 'approved', 'rejected'])->default('assigned');
            }

            if (!Schema::hasColumns('backups', ['requestor_employee_id'])) {
                $table->integer('requestor_employee_id');
            }

            if (!Schema::hasColumns('backups', ['location_lat'])) {
                $table->float('location_lat')->nullable();
            }

            if (!Schema::hasColumns('backups', ['location_long'])) {
                $table->float('location_long')->nullable();
            }
        });

        Schema::table('backup_histories', function (Blueprint $table) {
            if (!Schema::hasColumns('backup_histories', ['employee_id', 'notes'])) {
                $table->integer('employee_id');
                $table->text('notes')->nullable();
            }

            DB::statement("ALTER TABLE backup_histories DROP CONSTRAINT backup_histories_status_check;");
            DB::statement("ALTER TABLE backup_histories ADD CONSTRAINT backup_histories_status_check CHECK ((status)::TEXT = ANY (ARRAY [('assigned'::character varying)::TEXT, ('approved'::character varying)::TEXT, ('rejected'::character varying)::TEXT]));");
        });

        Schema::create('backup_times', function (Blueprint $table) {
            $table->id();
            $table->integer('backup_id');
            $table->date('backup_date');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->timestamps();
        });

        Schema::create('backup_employees', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');
            $table->integer('backup_id');
            $table->timestamps();
        });

        Schema::create('backup_employee_times', function (Blueprint $table) {
            $table->id();
            $table->integer('backup_time_id');
            $table->integer('employee_id');
            $table->timestamp('check_in_time')->nullable();
            $table->float('check_in_lat')->nullable();
            $table->float('check_in_long')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->float('check_out_lat')->nullable();
            $table->float('check_out_long')->nullable();
            $table->string('check_in_timezone')->nullable();
            $table->string('check_out_timezone')->nullable();
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
        Schema::dropIfExists('backup_times');
        Schema::dropIfExists('backup_employees');
        Schema::dropIfExists('backup_employee_times');
    }
};
