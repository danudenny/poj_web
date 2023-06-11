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
        Schema::create('companies', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name');
            $table->integer('odoo_company_id')->unique();
            $table->dateTime('create_date');
            $table->dateTime('write_date');
        });

        Schema::create('corporates', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name');
            $table->integer('odoo_corporate_id')->unique();
            $table->dateTime('create_date');
            $table->dateTime('write_date');
        });

        Schema::create('educationals', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name');
            $table->integer('odoo_educational_id')->unique();
            $table->dateTime('create_date');
            $table->dateTime('write_date');
        });

        Schema::create('kanwils', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name');
            $table->integer('odoo_kanwil_id')->unique();
            $table->dateTime('create_date');
            $table->dateTime('write_date');
        });

        Schema::create('outlets', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name');
            $table->integer('odoo_outlet_id')->unique();
            $table->dateTime('create_date');
            $table->dateTime('write_date');
        });

        Schema::create('cabang', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name');
            $table->integer('odoo_cabang_id')->unique();
            $table->dateTime('create_date');
            $table->dateTime('write_date');
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name');
            $table->integer('odoo_partner_id')->unique();
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
        Schema::dropIfExists('companies');
        Schema::dropIfExists('corporates');
        Schema::dropIfExists('educationals');
        Schema::dropIfExists('kanwils');
        Schema::dropIfExists('outlets');
        Schema::dropIfExists('cabang');
        Schema::dropIfExists('partners');
    }
};
