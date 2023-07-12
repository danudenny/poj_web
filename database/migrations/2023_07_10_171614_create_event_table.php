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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->integer('requestor_employee_id');
            $table->string('last_status');
            $table->enum('event_type', ['anggaran', 'non-anggaran']);
            $table->string('image_url');
            $table->string('title');
            $table->text('description');
            $table->float('latitude');
            $table->float('longitude');
            $table->text('address');
            $table->date('date_event');
            $table->time('time_event');
            $table->boolean('is_need_absence');
            $table->boolean('is_repeat');
            $table->enum('repeat_type', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->integer('repeat_every')->nullable();
            $table->string('repeat_days')->nullable();
            $table->date('repeat_end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('event_attendances', function (Blueprint $table) {
            $table->id();
            $table->integer('event_id');
            $table->integer('employee_id');
            $table->timestamps();
        });

        Schema::create('event_dates', function (Blueprint $table) {
            $table->id();
            $table->integer('event_id');
            $table->boolean('is_need_absence');
            $table->date('event_date');
            $table->time('event_time');
            $table->timestamps();
        });

        Schema::create('employee_events', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');
            $table->integer('event_id');
            $table->boolean('is_need_absence');
            $table->date('event_date');
            $table->time('event_time');
            $table->timestamps();
        });

        Schema::create('event_histories', function (Blueprint $table) {
            $table->id();
            $table->integer('event_id');
            $table->integer('employee_id');
            $table->enum('status', ['pending', 'approve', 'reject']);
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
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_attendances');
        Schema::dropIfExists('event_dates');
        Schema::dropIfExists('employee_events');
        Schema::dropIfExists('event_histories');
    }
};
