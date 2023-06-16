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
        Schema::table('employee_attendances', function(Blueprint $table) {
            $table->dateTime('real_check_out')->nullable(true)->change();
            $table->dropColumn('ettendance_type');
            $table->enum('attendance_type', ['onsite', 'offsite']);
            $table->integer('duration')->nullable(true)->change();
            $table->boolean('is_need_approval')->default(false);
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
