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
        Schema::table('users', function(Blueprint $table) {
            $table->boolean('is_normal_logged_in')->nullable(true)->default(false);
            $table->boolean('is_event_logged_in')->nullable(true)->default(false);
            $table->boolean('is_backup_logged_in')->nullable(true)->default(false);
            $table->boolean('is_overtime_logged_in')->nullable(true)->default(false);
            $table->boolean('is_longshift_logged_in')->nullable(true)->default(false);

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
