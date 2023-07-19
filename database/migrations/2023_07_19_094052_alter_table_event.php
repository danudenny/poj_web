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
        Schema::table('employee_events', function (Blueprint $table) {
            if (!Schema::hasColumns('employee_events', [
                'check_in_time', 'check_in_lat', 'check_in_long', 'check_in_timezone',
                'check_out_time', 'check_out_lat', 'check_out_long', 'check_out_timezone'
            ])) {
                $table->timestamp('check_in_time')->nullable();
                $table->float('check_in_lat')->nullable();
                $table->float('check_in_long')->nullable();
                $table->timestamp('check_out_time')->nullable();
                $table->float('check_out_lat')->nullable();
                $table->float('check_out_long')->nullable();
                $table->string('check_in_timezone')->nullable();
                $table->string('check_out_timezone')->nullable();
            }
        });

        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumns('events', ['timezone'])) {
                $table->string('timezone')->nullable();
            }
        });

        Schema::table('event_dates', function (Blueprint $table) {
            if (Schema::hasColumns('event_dates', ['event_date', 'event_time'])) {
                $table->dropColumn('event_date');
                $table->dropColumn('event_time');
            }

            if (!Schema::hasColumns('event_dates', ['event_datetime'])) {
                $table->timestamp('event_datetime');
            }
        });

        Schema::table('employee_events', function (Blueprint $table) {
            if (Schema::hasColumns('employee_events', ['event_date', 'event_time'])) {
                $table->dropColumn('event_date');
                $table->dropColumn('event_time');
            }

            if (!Schema::hasColumns('employee_events', ['event_datetime'])) {
                $table->timestamp('event_datetime');
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
