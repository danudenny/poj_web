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
        Schema::table('permissions', function(Blueprint $table) {
            //drop column view, create, edit, delete
            $table->dropColumn('view');
            $table->dropColumn('create');
            $table->dropColumn('edit');
            $table->dropColumn('delete');
            $table->string('group')->nullable();
            $table->string('ability')->nullable();
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