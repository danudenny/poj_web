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
        Schema::create('subsidiaries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->string('code')->nullable(false)->unique();
            $table->integer('outlet_id')->nullable();
            $table->timestamps();
        });
        Schema::create('subsidiary_childs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->string('code')->nullable(false)->unique();
            $table->timestamps();
        });
        Schema::create('subsidiary_parent_childs', function (Blueprint $table) {
            $table->integer('subsidiary_id');
            $table->integer('subsidiary_child_id');
        });
        Schema::table('employee_attendances', function (Blueprint $table) {
            $table->string('lat')->nullable(false);
            $table->string('long')->nullable(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subsidiaries');
    }
};
