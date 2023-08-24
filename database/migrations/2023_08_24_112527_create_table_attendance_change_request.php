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
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');
            $table->integer('employee_attendance_id');
            $table->string('reference_type');
            $table->integer('reference_id');
            $table->string('status');
            $table->string('check_in_time');
            $table->string('check_out_time');
            $table->text('notes');
            $table->string('file_url')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_correction_approvals', function (Blueprint $table) {
            $table->id();
            $table->integer('priority');
            $table->integer('attendance_correction_request_id');
            $table->integer('employee_id');
            $table->string('status');
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('attendance_correction_requests');
    }
};
