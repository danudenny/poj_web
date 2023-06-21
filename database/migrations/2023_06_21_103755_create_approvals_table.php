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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id()->index();
            $table->integer('approval_module_id');
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('approval_modules', function (Blueprint $table) {
            $table->id()->index();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('approval_users', function (Blueprint $table) {
            $table->integer('approval_id');
            $table->integer('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('approval_modules');
        Schema::dropIfExists('approval_users');
    }
};
