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
        Schema::table('overtimes', function (Blueprint $table) {
            if (Schema::hasColumns('overtimes', ['start_datetime', 'end_datetime'])) {
                $table->dropColumn('start_datetime');
                $table->dropColumn('end_datetime');
            }

            if (!Schema::hasColumn('overtimes', 'job_id')) {
                $table->integer('job_id');
            }
            if (!Schema::hasColumn('overtimes', 'start_date')) {
                $table->date('start_date');
            }
            if (!Schema::hasColumn('overtimes', 'end_date')) {
                $table->date('end_date');
            }
        });

        Schema::table('overtime_employees', function (Blueprint $table) {
            if (Schema::hasColumn('overtime_employees', 'overtime_id')) {
                $table->dropColumn('overtime_id');
            }

            if (!Schema::hasColumn('overtime_employees', 'overtime_date_id')) {
                $table->integer('overtime_date_id');
            }
        });

        Schema::create('overtime_dates', function (Blueprint $table) {
            $table->id();
            $table->integer('overtime_id');
            $table->date('date');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
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
        Schema::dropIfExists('overtime_dates');
    }
};
