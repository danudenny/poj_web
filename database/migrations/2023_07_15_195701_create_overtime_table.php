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
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->integer('requestor_employee_id');
            $table->integer('unit_relation_id');
            $table->enum('last_status', ['pending', 'approved', 'rejected', 'finished'])->nullable();
            $table->timestamp('last_status_at')->nullable();
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime');
            $table->string('timezone');
            $table->text('notes');
            $table->text('image_url')->nullable();
            $table->float('location_lat')->nullable();
            $table->float('location_long')->nullable();
            $table->timestamps();
        });

        Schema::create('overtime_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('overtime_id');
            $table->integer('employee_id');
            $table->enum('history_type', ['pending', 'check-in', 'check-out', 'approved', 'rejected', 'finished']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('overtime_employees', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');
            $table->integer('overtime_id');
            $table->timestamp('check_in_time')->nullable();
            $table->float('check_in_lat')->nullable();
            $table->float('check_in_long')->nullable();
            $table->timestamp('check_out_time')->nullable();
            $table->float('check_out_lat')->nullable();
            $table->float('check_out_long')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('overtimes');
        Schema::dropIfExists('overtime_histories');
        Schema::dropIfExists('overtime_employees');
    }
};
