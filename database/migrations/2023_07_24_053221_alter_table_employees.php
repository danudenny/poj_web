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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('agama_id')->change();
            $table->string('ptkp_id')->change();
            $table->string('baju_id')->change();
            $table->string('celana_id')->change();
            $table->string('sepatu_id')->change();
            $table->string('darah_id')->change();
            $table->string('educational_id')->change();
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
