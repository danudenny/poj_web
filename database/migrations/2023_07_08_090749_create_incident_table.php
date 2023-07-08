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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');
            $table->enum('category', ['internal', 'external']);
            $table->string('name');
            $table->float('latitude');
            $table->float('longitude');
            $table->string('location_name');
            $table->timestamp('incident_time');
            $table->string('person');
            $table->string('witness');
            $table->text('cause');
            $table->text('chronology');
            $table->string('last_stage');
            $table->string('last_status');
            $table->text('incident_analysis')->nullable();
            $table->text('follow_up_incident')->nullable();
            $table->timestamps();
        });

        Schema::create('incident_images', function(Blueprint $table) {
            $table->id();
            $table->integer('incident_id');
            $table->enum('image_type', ['incident', 'follow-up']);
            $table->string('image_url');
            $table->timestamps();
        });

        Schema::create('incident_histories', function(Blueprint $table) {
            $table->id();
            $table->integer('incident_id');
            $table->enum('history_type', ['submit', 'follow-up', 'closure']);
            $table->enum('status', ['submitted', 'reject', 'approve', 'close', 'disclose']);
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('incident_images');
        Schema::dropIfExists('incident_histories');
    }
};
