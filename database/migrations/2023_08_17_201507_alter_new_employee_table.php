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
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('company_id');
            $table->dropColumn('country_id');
            $table->dropColumn('certificate');
            $table->dropColumn('age');
            $table->dropColumn('is_bpjs_kesehatan');
            $table->dropColumn('is_bpjs_ketenagakerjaan');
            $table->dropColumn('is_jamsostek');
            $table->dropColumn('is_insurance');
            $table->dropColumn('is_kta');
            $table->dropColumn('bpjs_kesehatan');
            $table->dropColumn('bpjs_ketenagakerjaan');
            $table->dropColumn('jamsostek');
            $table->dropColumn('insurance');
            $table->dropColumn('kartu_keluarga');
            $table->dropColumn('kta');
            $table->dropColumn('expired_kta');
            $table->dropColumn('ptkp_id');
            $table->dropColumn('batas_lembur_id');
            $table->dropColumn('baju_id');
            $table->dropColumn('celana_id');
            $table->dropColumn('sepatu_id');
            $table->dropColumn('darah_id');
            $table->dropColumn('nickname');
            $table->dropColumn('educational_id');
            $table->dropColumn('tgl_keluar');
            $table->dropColumn('activity_type_id');
            $table->dropColumn('address_id');
            $table->dropColumn('parent_id');
            $table->dropColumn('coach_id');
            $table->dropColumn('tz');
            $table->dropColumn('hr_presence_state');
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
