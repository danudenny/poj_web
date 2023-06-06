<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('syncs', function (Blueprint $table) {
            $table->id();
            $table->string('odoo_model');
            $table->integer('odoo_record_id');
            $table->string('status');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('syncs');
    }
};
