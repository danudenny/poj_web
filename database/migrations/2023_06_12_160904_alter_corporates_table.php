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
    public function up(): void
    {
        Schema::table('corporates', function (Blueprint $table) {
            $table->string('code')->nullable(true)->unique()->after('id');
        });

        Schema::table('kanwils', function (Blueprint $table) {
            $table->string('code')->nullable(true)->unique()->after('id');
            $table->integer('corporate_id')->nullable(true)->after('id');
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id()->index();
            $table->string('code')->nullable(true)->unique();
            $table->integer('kanwil_id')->nullable(true);
            $table->string('name')->nullable(true);
            $table->dateTime('create_date');
            $table->dateTime('write_date');
        });

        Schema::table('cabang', function (Blueprint $table) {
            $table->string('code')->nullable(true)->unique()->after('id');
            $table->integer('area_id')->nullable(true)->after('id');
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->string('code')->nullable(true)->unique()->after('id');
            $table->integer('cabang_id')->nullable(true)->after('id');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->integer('department_id')->nullable(true)->after('id');
            $table->integer('company_id')->nullable(true)->after('id');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->integer('company_id')->nullable(true)->after('id');
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
