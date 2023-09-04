<?php

use App\Models\TimesheetReportDetail;
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
        Schema::table('timesheet_report_details', function (Blueprint $table) {
            if (!Schema::hasColumns('timesheet_report_details', ['response_message', 'status'])) {
                $table->string('response_message')->nullable();
                $table->string('status')->default(TimesheetReportDetail::StatusPending);
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
