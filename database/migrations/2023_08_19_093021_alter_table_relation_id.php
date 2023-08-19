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
        // Approval
        Schema::table('approvals', function(Blueprint $table) {
            $table->string('unit_relation_id')->change();
        });
        Schema::table('approval_users', function(Blueprint $table) {
            $table->string('unit_relation_id')->change();
        });

        // Operating Unit
        Schema::table('operating_unit_details', function(Blueprint $table) {
            $table->string('unit_relation_id')->change();
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE operating_unit_details ALTER COLUMN operating_unit_corporate_id TYPE integer USING (operating_unit_corporate_id)::integer");
        });
        Schema::table('operating_unit_corporates', function(Blueprint $table) {
            $table->string('corporate_relation_id')->change();
            $table->string('operating_unit_relation_id')->change();
        });

        Schema::table('backups', function (Blueprint $table) {
            $table->string('source_unit_relation_id')->change();
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
