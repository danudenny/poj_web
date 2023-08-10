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
        Schema::table('operating_unit_details', function (Blueprint $table) {
            if (!Schema::hasColumn('operating_unit_details', 'unit_level')) {
                $table->integer('unit_level')->nullable();
            }

            if (Schema::hasColumn('operating_unit_details', 'kanwil_relation_id')) {
                $table->renameColumn('kanwil_relation_id', 'unit_relation_id');
            }
        });

        Schema::table('operating_unit_corporates', function (Blueprint $table) {
            if (!Schema::hasColumn('operating_unit_corporates', 'unit_level')) {
                $table->integer('unit_level')->nullable();
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
        //
    }
};
