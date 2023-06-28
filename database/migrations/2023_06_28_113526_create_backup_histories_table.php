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
        Schema::dropIfExists('backup_history');
        Schema::create('backup_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('backup_id');
            $table->enum('status', ['assigned', 'in_progress', 'completed', 'canceled']);
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
        Schema::dropIfExists('backup_histories');
    }
};
