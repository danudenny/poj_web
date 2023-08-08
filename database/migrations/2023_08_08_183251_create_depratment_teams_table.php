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
        Schema::create('teams', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name')->unique();
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('department_has_teams', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->index();
            $table->unsignedBigInteger('team_id')->index();
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('depratment_teams');
    }
};
