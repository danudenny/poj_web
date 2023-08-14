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
        Schema::table('units', function (Blueprint $table) {
            $table->unsignedBigInteger('relation_id')->nullable()->change();
            $table->dropColumn('create_date');
            $table->dropColumn('write_date');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->change();
            $table->unsignedBigInteger('department_id')->nullable()->change();
        });

        Schema::table('department_has_teams', function (Blueprint $table) {
            $table->integer('unit_level')->nullable();
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
