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
        Schema::table("backup_approvals", function(Blueprint $table) {
            if (Schema::hasColumn('backup_approvals', 'user_id')) {
                $table->renameColumn('user_id', 'employee_id');
            }
        });

        Schema::table("event_approvals", function(Blueprint $table) {
            if (Schema::hasColumn('event_approvals', 'user_id')) {
                $table->renameColumn('user_id', 'employee_id');
            }
        });

        Schema::table("overtime_approvals", function(Blueprint $table) {
            if (Schema::hasColumn('overtime_approvals', 'user_id')) {
                $table->renameColumn('user_id', 'employee_id');
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
