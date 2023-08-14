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
            if (!Schema::hasColumns('approval_users', ['department_id', 'team_id', 'odoo_job_id'])) {
                $table->integer('department_id')->nullable();
                $table->integer('team_id')->nullable();
                $table->integer('odoo_job_id')->nullable();
            }
        });

        Schema::table('approvals', function (Blueprint $table) {
            if (!Schema::hasColumns('approvals', ['department_id', 'team_id', 'odoo_job_id'])) {
                $table->integer('department_id')->nullable();
                $table->integer('team_id')->nullable();
                $table->integer('odoo_job_id')->nullable();
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
