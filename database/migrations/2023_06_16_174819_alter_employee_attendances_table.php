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
           $table->string('checkout_type')->nullable(true)->change();
           $table->float('checkin_real_radius')->nullable(true);
           $table->float('checkout_real_radius')->nullable(true);
           $table->boolean('is_late')->nullable(true);
            $table->boolean('is_early')->nullable(true);
            $table->string('late_reason')->nullable(true);
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
