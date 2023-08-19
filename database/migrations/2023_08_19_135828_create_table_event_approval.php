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
        Schema::dropIfExists('event_approvals');

        Schema::create('event_approvals', function (Blueprint $table) {
            $table->id();
            $table->integer('priority');
            $table->integer('event_id');
            $table->integer('employee_id');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->integer('priority');
            $table->integer('leave_request_id');
            $table->integer('employee_id');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('incident_approvals', function (Blueprint $table) {
            $table->id();
            $table->integer('priority');
            $table->integer('incident_id');
            $table->integer('employee_id');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign('leave_requests_employee_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_approvals');
        Schema::dropIfExists('leave_request_approvals');
        Schema::dropIfExists('incident_approvals');
    }
};
