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
        Schema::table('admin_units', function (Blueprint $table) {
            $table->string('unit_relation_id')->change();
        });
        Schema::table('unit_has_jobs', function (Blueprint $table) {
            $table->string('unit_relation_id')->change();
        });
        Schema::table('unit_jobs', function (Blueprint $table) {
            $table->dropForeign('unit_jobs_unit_id_foreign');
        });
        Schema::table('unit_jobs', function (Blueprint $table) {
            $table->string('unit_id')->change();
        });
        Schema::table('approval_users', function (Blueprint $table) {
            $table->string('unit_relation_id')->change();
        });
        Schema::table('backups', function (Blueprint $table) {
            $table->string('unit_id')->change();
        });
        Schema::table('central_operating_unit_users', function (Blueprint $table) {
            $table->string('unit_relation_id')->change();
        });
        Schema::table('department_has_teams', function (Blueprint $table) {
            $table->string('unit_id')->change();
        });
        Schema::table('employee_timesheet', function (Blueprint $table) {
            $table->string('unit_id')->change();
        });
        Schema::table('operating_unit_corporates', function (Blueprint $table) {
            $table->string('operating_unit_relation_id')->change();
            $table->string('corporate_relation_id')->change();
        });
        Schema::table('operating_unit_details', function (Blueprint $table) {
            $table->string('operating_unit_corporate_id')->change();
            $table->string('unit_relation_id')->change();
        });
        Schema::table('operating_unit_users', function (Blueprint $table) {
            $table->string('operating_unit_corporate_id')->change();
        });
        Schema::table('overtimes', function (Blueprint $table) {
            $table->string('unit_relation_id')->change();
        });
        Schema::table('teams', function (Blueprint $table) {
            $table->string('description')->nullable()->change();
        });
        Schema::dropIfExists('actions');
        Schema::dropIfExists('cabang');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('corporates');
        Schema::dropIfExists('educationals');
        Schema::dropIfExists('employee_units');
        Schema::dropIfExists('kanwils');
        Schema::dropIfExists('outlets');
        Schema::dropIfExists('subsidaries');
        Schema::dropIfExists('subsidary_childs');
        Schema::dropIfExists('subsidary_parent_childs');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('work_locations');
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
