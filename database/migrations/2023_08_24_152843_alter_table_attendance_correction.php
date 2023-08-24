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
        Schema::table('attendance_correction_requests', function (Blueprint $table) {
            if (!Schema::hasColumns('attendance_correction_requests', ['correction_date', 'correction_type'])) {
                $table->date('correction_date')->nullable();
                $table->string('correction_type')->nullable();
            }
        });

        Schema::table('employee_attendances', function (Blueprint $table) {
            if (!Schema::hasColumns('employee_attendances', ['notes'])) {
                $table->string('notes')->nullable();
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
