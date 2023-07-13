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
        Schema::create('late_checkin_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->integer('late_15')->default(0);
            $table->integer('late_30')->default(0);
            $table->integer('late_45')->default(0);
            $table->integer('late_60')->default(0);
            $table->integer('late_75')->default(0);
            $table->integer('late_90')->default(0);
            $table->integer('late_105')->default(0);
            $table->integer('late_120')->default(0);
            $table->integer('total_late_15')->default(0);
            $table->integer('total_late_30')->default(0);
            $table->integer('total_late_45')->default(0);
            $table->integer('total_late_60')->default(0);
            $table->integer('total_late_75')->default(0);
            $table->integer('total_late_90')->default(0);
            $table->integer('total_late_105')->default(0);
            $table->integer('total_late_120')->default(0);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('late_checkin_reports');
    }
};
