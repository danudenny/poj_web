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
//        Schema::create('roles', function (Blueprint $table) {
//            $table->id();
//            $table->string('name');
//            $table->enum('role_type', ['admin_corporate', 'admin_unit', 'user_staff', 'user_approval', 'superadmin', ''])->default('user');
//            $table->integer('role_level')->default(1);
//        });
//
//        Schema::create('permissions', function (Blueprint $table) {
//            $table->id();
//            $table->string('permission_name');
//        });
//
//        Schema::create('actions', function (Blueprint $table) {
//            $table->id();
//            $table->string('action_name');
//        });
//
//        Schema::create('role_permissions', function (Blueprint $table) {
//            $table->id();
//            $table->unsignedBigInteger('role_id');
//            $table->unsignedBigInteger('permission_id');
//            $table->unsignedBigInteger('action_id');
//
//            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
//            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
//            $table->foreign('action_id')->references('id')->on('actions')->onDelete('cascade');
//        });
//
//        Schema::create('user_roles', function (Blueprint $table) {
//            $table->unsignedBigInteger('user_id');
//            $table->unsignedBigInteger('role_id');
//
//            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
//            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('actions');
        Schema::dropIfExists('user_roles');
    }
};
