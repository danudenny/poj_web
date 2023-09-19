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
        Schema::create('master_overtime_limit', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('odoo_overtime_limit_id')->unique();
            $table->integer('daily_work')->default(0);
            $table->integer('public_holiday')->default(0);
            $table->integer('day_off')->default(0);
            $table->integer('sequence')->default(0);
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'odoo_overtime_limit_id')) {
                    $table->integer('odoo_overtime_limit_id')->nullable()->default(null);
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
        Schema::dropIfExists('master_overtime_limit');
    }
};
