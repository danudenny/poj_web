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
            if (!Schema::hasColumns('employee_attendances', ['check_in_image_url', 'check_out_image_url', 'check_in_notes'])) {
                $table->string('check_in_image_url')->nullable();
                $table->string('check_out_image_url')->nullable();
                $table->string('check_in_notes')->nullable();
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
