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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->integer('odoo_employee_id')->nullable(true);
            $table->string('name')->nullable(true);
            $table->boolean('is_active')->default(true);
            $table->integer('company_id')->nullable(true);
            $table->enum('gender', ['male', 'female']);
            $table->integer('country_id')->nullable(true);
            $table->enum('marital', ['single', 'married'])->default('single');
            $table->string('certificate')->nullable(true);
            $table->date('first_contract_date')->nullable(true);
            $table->integer('age')->nullable(true);
            $table->integer('agama_id')->nullable(true);
            $table->boolean('is_bpjs_kesehatan')->nullable(true)->default(false);
            $table->boolean('is_bpjs_ketenagakerjaan')->nullable(true)->default(false);
            $table->boolean('is_jamsostek')->nullable(true)->default(false);
            $table->boolean('is_insurance')->nullable(true)->default(false);
            $table->boolean('is_kta')->nullable(true)->default(false);
            $table->string('bpjs_kesehatan')->nullable(true);
            $table->string('bpjs_ketenagakerjaan')->nullable(true);
            $table->string('jamsostek')->nullable(true);
            $table->string('insurance')->nullable(true);
            $table->string('kartu_keluarga')->nullable(true);
            $table->string('kta')->nullable(true);
            $table->boolean('expired_kta')->nullable(true)->default(false);
            $table->integer('ptkp_id')->nullable(true);
            $table->integer('batas_lembur_id')->nullable(true);
            $table->integer('baju_id')->nullable(true);
            $table->integer('celana_id')->nullable(true);
            $table->integer('sepatu_id')->nullable(true);
            $table->integer('darah_id')->nullable(true);
            $table->string('nickname')->nullable(true);
            $table->integer('educational_id')->nullable(true);
            $table->string('npwp')->nullable(true);
            $table->integer('corporate_id')->nullable(true);
            $table->integer('kanwil_id')->nullable(true);
            $table->integer('area_id')->nullable(true);
            $table->integer('cabang_id')->nullable(true);
            $table->integer('outlet_id')->nullable(true);
            $table->string('tgl_keluar')->nullable(true);
            $table->integer('partner_id')->nullable(true);
            $table->string('employee_category')->nullable(true);
            $table->integer('identification_id')->nullable(true);
            $table->string('registration_number')->nullable(true);
            $table->integer('customer_id')->nullable(true);
            $table->integer('activity_type_id')->nullable(true);
            $table->integer('department_id')->nullable(true);
            $table->integer('job_id')->nullable(true);
            $table->integer('address_id')->nullable(true);
            $table->string('work_phone')->nullable(true);
            $table->string('mobile_phone')->nullable(true);
            $table->string('work_email')->nullable(true);
            $table->string('work_location')->nullable(true);
            $table->integer('parent_id')->nullable(true);
            $table->integer('coach_id')->nullable(true);
            $table->string('tz')->nullable(true);
            $table->string('hr_presence_state')->nullable(true);
            $table->integer('default_operating_unit_id')->nullable(true);
            $table->string('create_date')->nullable(true);
            $table->string('write_date')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
