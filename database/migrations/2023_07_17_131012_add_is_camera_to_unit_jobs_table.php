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
        Schema::table('unit_jobs', function (Blueprint $table) {
            $table->boolean('is_camera')->default(false);
            $table->boolean('is_upload')->default(false);
            $table->boolean('is_reporting')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('unit_jobs', function (Blueprint $table) {
            //
        });
    }
};
