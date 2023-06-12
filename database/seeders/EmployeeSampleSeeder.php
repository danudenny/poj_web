<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $employee = [
                        "odoo_employee_id"=> 129005,
                        "name"=> "A FIRMAN AMAR",
                        "is_active"=> true,
                        "company_id"=> 1,
                        "gender"=> "male",
                        "marital"=> "single",
                        "first_contract_date"=> "2022-04-01",
                        "age"=> "0",
                        "agama_id"=> 1,
                        "is_bpjs_kesehatan"=> true,
                        "is_bpjs_ketenagakerjaan"=> true,
                        "is_jamsostek"=> false,
                        "is_insurance"=> false,
                        "is_kta"=> false,
                        "bpjs_kesehatan"=> "1769619251",
                        "bpjs_ketenagakerjaan"=> "15012597876",
                        "jamsostek"=> "",
                        "insurance"=> "",
                        "kartu_keluarga"=> "",
                        "kta"=> "",
                        "ptkp_id"=> 7,
                        "baju_id"=> 0,
                        "celana_id"=> 0,
                        "sepatu_id"=> 0,
                        "darah_id"=> 0,
                        "nickname"=> "firman",
                        "educational_id"=> 7,
                        "npwp"=> "719194003806000",
                        "corporate_id"=> 0,
                        "kanwil_id"=> 11,
                        "area_id"=> 222,
                        "cabang_id"=> 5202,
                        "outlet_id"=> 25248,
                        "partner_id"=> 0,
                        "employee_category"=> "karyawan_outsourcing",
                        "identification_id"=> 7371110, //kena error numeric length int
                        "registration_number"=> "PQ0604793",
                        "customer_id"=> 6269,
                        "activity_type_id"=> 0,
                        "department_id"=> 0,
                        "job_id"=> 265,
                        "address_id"=> 0,
                        "work_phone"=> "",
                        "mobile_phone"=> "82310066672",
                        "work_email"=> "anharsinjai@gmail.com",
                        "work_location"=> "",
                        "tz"=> "Asia/Jakarta",
                        "default_operating_unit_id"=> 1,
                        "create_date"=> "2022-08-01 07:56:11",
                        "write_date"=> "2023-06-08 07:56:42"
        ];

        DB::table('employees')->insert($employee);

    }
}
