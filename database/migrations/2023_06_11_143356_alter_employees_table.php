<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('identification_id')->nullable(true)->change();
            $table->string('marital')->nullable(true)->change();
            $table->string('gender')->nullable(true)->change();
            $table->integer('odoo_employee_id')->index('odoo_employee_id_index')->unique()->change();
        });
        DB::statement('ALTER TABLE employees DROP CONSTRAINT "employees_marital_check"');
        DB::statement('ALTER TABLE employees DROP CONSTRAINT "employees_gender_check"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE employees DROP CONSTRAINT "employees_marital_check"');
        DB::statement('ALTER TABLE employees DROP CONSTRAINT "employees_gender_check"');
    }
};
