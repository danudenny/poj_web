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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumns('users', ['is_password_changed', 'is_policy_confirmed'])) {
                $table->boolean('is_password_changed')->default(false);
                $table->boolean('is_policy_confirmed')->default(false);
            }
        });

        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->string('created_by');
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumns('employees', ['bpjs_kesehatan', 'bpjs_ketenagakerjaan'])) {
                $table->string('bpjs_kesehatan')->nullable();
                $table->string('bpjs_ketenagakerjaan')->nullable();
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
        Schema::dropIfExists('policies');
    }
};
