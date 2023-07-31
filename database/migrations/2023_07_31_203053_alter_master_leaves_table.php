<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('master_leaves', function (Blueprint $table) {
            DB::statement('ALTER TABLE master_leaves DROP CONSTRAINT master_leaves_leave_type_check;');
            DB::statement('ALTER TABLE master_leaves ADD CONSTRAINT master_leaves_leave_type_check CHECK (leave_type IN (\'leave\', \'permit\'));');
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
