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
        Schema::create('operating_unit_corporates', function (Blueprint $table) {
            $table->id();
            $table->integer('kantor_perwakilan_id');
            $table->integer('corporate_relation_id');
            $table->timestamps();
        });

        Schema::create('operating_unit_kanwils', function (Blueprint $table) {
            $table->id();
            $table->integer('operating_unit_corporate_id');
            $table->integer('kanwil_relation_id');
            $table->timestamps();
        });
        Schema::create('operating_unit_users', function (Blueprint $table) {
            $table->id();
            $table->integer('operating_unit_corporate_id');
            $table->integer('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operating_unit_corporates');
        Schema::dropIfExists('operating_unit_kanwils');
        Schema::dropIfExists('operating_unit_users');
    }
};
