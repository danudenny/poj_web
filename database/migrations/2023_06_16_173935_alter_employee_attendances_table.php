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
            $table->dropColumn('attendance_type');
            $table->dropColumn('lat');
            $table->dropColumn('long');
            $table->float('checkin_lat')->nullable(false);
            $table->float('checkin_long')->nullable(true);
            $table->float('checkout_lat')->nullable(true);
            $table->float('checkout_long')->nullable(true);
            $table->enum('checkin_type', ['onsite', 'offsite']);
            $table->enum('checkout_type', ['onsite', 'offsite']);
            $table->enum('attendance_types', ['normal', 'backup', 'event', 'overtime'])->nullable(true);
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
