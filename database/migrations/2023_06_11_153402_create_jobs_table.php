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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id()->index();
            $table->integer('odoo_job_id')->unique();
            $table->string('name');
            $table->dateTime('create_date');
            $table->dateTime('write_date');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id()->index();
            $table->integer('odoo_departments_id')->unique();
            $table->string('name');
            $table->dateTime('create_date');
            $table->dateTime('write_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('departments');
    }
};
