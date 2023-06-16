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
        Schema::table('employee_details', function(Blueprint $table) {
            $table->dropColumn('ettendance_type');
            $table->enum('work_arrangement', ['on_site', 'off_site', 'hybrid'])->default('on_site')->after('employee_timesheet_id');
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
