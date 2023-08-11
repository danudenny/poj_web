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
        Schema::table('approval_users', function (Blueprint $table) {
            if (!Schema::hasColumns('approval_users', ['unit_relation_id', 'unit_level'])) {
                $table->integer('unit_relation_id')->nullable();
                $table->integer('unit_level')->nullable();
            }

            if (Schema::hasColumn('approval_users', 'user_id')) {
                $table->renameColumn('user_id', 'employee_id');
            }
        });

        Schema::table('approvals', function (Blueprint $table) {
            if (Schema::hasColumn('approvals', 'unit_id')) {
                $table->dropColumn('unit_id');
            }

            if (!Schema::hasColumn('approvals', 'unit_relation_id')) {
                $table->integer('unit_relation_id')->nullable();
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
