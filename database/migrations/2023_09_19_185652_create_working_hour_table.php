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
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->integer('odoo_working_hour_id')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('working_hour_details', function (Blueprint $table) {
            $table->id();
            $table->integer('odoo_working_hour_id');
            $table->integer('odoo_working_hour_detail_id')->unique();
            $table->string('name');
            $table->string('day_period');
            $table->float('hour_from');
            $table->float('hour_to');
            $table->integer('day_of_week');
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'odoo_working_hour_id')) {
                $table->integer('odoo_working_hour_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('working_hours');
        Schema::dropIfExists('working_hour_details');
    }
};
