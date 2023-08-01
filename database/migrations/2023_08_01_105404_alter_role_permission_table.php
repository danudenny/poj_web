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
        // drop column action_id
//        Schema::table('role_permissions', function (Blueprint $table) {
//            $table->dropColumn('action_id');
//            $table->boolean('is_create')->default(false);
//            $table->boolean('is_read')->default(false);
//            $table->boolean('is_update')->default(false);
//            $table->boolean('is_delete')->default(false);
//        });
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
