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
        Schema::table('employee_timesheet', function (Blueprint $table) {
            $table->index('unit_id');
            $table->index('shift_type');
        });

        Schema::table('units', function (Blueprint $table) {
            if (!Schema::hasColumn('units', 'timezone')) {
                $table->string('timezone')->nullable();
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
        Schema::table('employee_timesheet', function (Blueprint $table) {
            $table->dropIndex('employee_timesheet_unit_id_index');
            $table->dropIndex('employee_timesheet_shift_type_index');
        });
    }
};
