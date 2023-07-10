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
        Schema::table('incident_histories', function(Blueprint $table) {
            $table->text('incident_analysis')->nullable();
            $table->text('follow_up_incident')->nullable();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('incident_analysis');
            $table->dropColumn('follow_up_incident');
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
