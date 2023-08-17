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
        Schema::table('unit_jobs', function(Blueprint $table) {
            if (!Schema::hasColumns('unit_jobs', ['total_normal', 'total_overtime', 'total_backup'])) {
                $table->integer('total_normal')->default(0);
                $table->integer('total_overtime')->default(0);
                $table->integer('total_backup')->default(0);
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
