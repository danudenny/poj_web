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
        Schema::table('operating_unit_corporates', function(Blueprint $table) {
            if (Schema::hasColumn('operating_unit_corporates', 'kantor_perwakilan_id')) {
                $table->renameColumn('kantor_perwakilan_id', 'operating_unit_relation_id');
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
