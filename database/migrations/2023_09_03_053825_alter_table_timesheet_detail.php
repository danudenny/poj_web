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
        Schema::table('timesheet_report_details', function (Blueprint $table) {
            if (!Schema::hasColumns('timesheet_report_details', ['odoo_payslip_id'])) {
                $table->string('odoo_payslip_id')->nullable();
            }
        });

        Schema::table('timesheet_reports', function (Blueprint $table) {
            if (!Schema::hasColumns('timesheet_reports', ['last_sent_at', 'last_sent_by'])) {
                $table->timestamp('last_sent_at')->nullable();
                $table->string('last_sent_by')->nullable();
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
