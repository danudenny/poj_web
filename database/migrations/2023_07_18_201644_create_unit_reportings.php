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
        Schema::table('unit_jobs', function (Blueprint $table) {
            $table->enum('type', ['normal', 'multiple', 'none'])->default('none');
            $table->integer('total_reporting')->default(0);
            $table->json('reporting_names')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unit_reportings');
    }
};
