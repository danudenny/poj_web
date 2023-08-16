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
        Schema::table('employee_timesheet', function(Blueprint $table) {
            $table->string('start_time')->nullable()->change();
            $table->string('end_time')->nullable()->change();
            $table->dropColumn('days');
        });

        Schema::table('employee_timesheet_days', function(Blueprint $table) {
            $table->dropColumn('days');
            $table->string('day')->nullable();
            $table->string('start_time')->nullable()->change();
            $table->string('end_time')->nullable()->change();
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
