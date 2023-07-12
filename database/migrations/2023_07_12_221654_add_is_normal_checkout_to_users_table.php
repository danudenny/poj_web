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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean("is_normal_checkout")->default(false);
            $table->boolean("is_backup_checkout")->default(false);
            $table->boolean("is_event_checkout")->default(false);
            $table->boolean("is_longshift_checkout")->default(false);
            $table->boolean("is_overtime_checkout")->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
