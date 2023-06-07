<?php

namespace App\Console\Commands;

use App\Models\Employee;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncOdooData extends Command
{
    protected $signature = 'sync:odoo:employees';
    protected $description = 'Sync employee data from Odoo';

    /**
     * @throws GuzzleException
     */
    public function handle()
    {
        // Configure Odoo JSON-RPC API credentials
        $odooUrl = config('app.odoo.odooUrl');
        $odooDb = config('app.odoo.database');
        $odooUsername = config('app.odoo.username');
        $odooPassword = config('app.odoo.password');
        $odooUri =config('app.odoo.odooUri');

        // Connect to Odoo JSON-RPC API
        $client = new Client(['base_uri' => $odooUrl]);

        // Authenticate with Odoo JSON-RPC API
        $response = $client->post($odooUri, [
            'json' => [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'service' => 'object',
                    'method' => 'execute_kw',
                    'args' => [
                        $odooDb,
                        2,
                        $odooPassword,
                        'hr.employee',
                        'search_read',
                        [[]],
                        [
                            'fields' => [
                                'id',
                                'name',
                                'active',
                                'company_id',
                                'gender',
                                'country_id',
                                'marital',
                                'certificate',
                                'contract_ids',
                                'first_contract_date',
                                'age',
                                'equipment_ids',
                                'equipment_request_ids',
                                'personal_equipment_ids',
                                'sms_agama_id',
                                'sms_is_bpjs_kesehatan',
                                'sms_is_bpjs_ketenagakerjaan',
                                'sms_is_jamsostek',
                                'sms_is_insurance',
                                'sms_is_kta',
                                'sms_bpjs_kesehatan',
                                'sms_bpjs_ketenagakerjaan',
                                'sms_jamsostek',
                                'sms_insurance',
                                'sms_kartu_keluarga',
                                'sms_kta',
                                'sms_expired_kta',
                                'sms_ptkp_id',
                                'sms_batas_lembur_id',
                                'sms_baju_id',
                                'sms_celana_id',
                                'sms_sepatu_id',
                                'sms_darah_id',
                                'sms_nick',
                                'sms_educational_id',
                                'sms_npwp',
                                'sms_corporate_id',
                                'sms_kanwil_id',
                                'sms_area_id',
                                'sms_cabang_id',
                                'sms_outlet_id',
                                'tgl_keluar',
                                'sms_partner_id',
                                'employee_categ',
                                'identification_id',
                                'registration_number',
                                'customer_id',
                                'activity_type_id',
                                'department_id',
                                'job_id',
                                'address_id',
                                'work_phone',
                                'mobile_phone',
                                'work_email',
                                'work_location',
                                'parent_id',
                                'coach_id',
                                'tz',
                                'hr_presence_state',
                                'attendance_ids',
                                'operating_unit_ids',
                                'default_operating_unit_id',
                                'create_date',
                                'write_date'
                            ],
                            'limit' => 100,
                        ],
                    ],
                ],
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if (array_key_exists('error', $result)) {
            $this->error('Failed to authenticate with Odoo: ' . $result['error']['message']);
            return;
        }

        // Sync employee data with PostgreSQL database
        foreach ($result['result'] as $employee) {
            $existingEmployee = Employee::
                where(
                    'odoo_employee_id',
                    '=',
                    $employee['id']
                )->first();

            if ($existingEmployee) {
                // Check if the 'updated_at' timestamp is greater than 'created_at'
                if ($employee['write_date'] > $employee['create_date']) {
                    Employee::
                        where('odoo_employee_id', $employee['id'])
                        ->update([
                            'odoo_employee_id' => $employee['id'], #int
                            'name' => $employee['name'], #string
                            'is_active' => $employee['active'] == 0 ? true : false,
                            'company_id' => $employee['company_id'][0] ?? null, #int -> companies
                            'gender' => $employee['gender'] ?? 'male',
                            'country_id' => $employee['country_id'][0] ?? null, #int -> countries
                            'marital' => $employee['marital'], #enum[single,married]
                            'certificate' => $employee['certificate'], #string
                            'first_contract_date' => $employee['first_contract_date'] ?? null, #date
                            'age' => $employee['age'], #int
                            'agama_id' => $employee['sms_agama_id'][0] ?? null, #int -> religions
                            'is_bpjs_kesehatan' => $employee['sms_is_bpjs_kesehatan'] == 0 ? true : false, #bool
                            'is_bpjs_ketenagakerjaan' => $employee['sms_is_bpjs_ketenagakerjaan'] == 0 ? true : false, #bool
                            'is_jamsostek' => $employee['sms_is_jamsostek'] == 0 ? true : false, #bool
                            'is_insurance' => $employee['sms_is_insurance'] == 0 ? true : false, #bool
                            'is_kta' => $employee['sms_is_kta'] == 0 ? true : false, #bool
                            'bpjs_kesehatan' => $employee['sms_bpjs_kesehatan'], #string
                            'bpjs_ketenagakerjaan' => $employee['sms_bpjs_ketenagakerjaan'], #string
                            'jamsostek' => $employee['sms_jamsostek'], #string
                            'insurance' => $employee['sms_insurance'], #string
                            'kartu_keluarga' => $employee['sms_kartu_keluarga'], #string
                            'kta' => $employee['sms_kta'], #string
                            'expired_kta' => $employee['sms_expired_kta'] == 0 ? true : false, #boolean
                            'ptkp_id' => $employee['sms_ptkp_id'][0] ?? null, #int -> ptkp
                            'baju_id' => $employee['sms_baju_id'], #int -> clothes
                            'celana_id' => $employee['sms_celana_id'], #int -> clothes
                            'sepatu_id' => $employee['sms_sepatu_id'], #int -> clothes
                            'darah_id' => $employee['sms_darah_id'], #int
                            'nickname' => $employee['sms_nick'], #string
                            'educational_id' => $employee['sms_educational_id'][0] ?? null, #int -> educations
                            'npwp' => $employee['sms_npwp'], #string
                            'corporate_id' => $employee['corporate_id'][0] ?? null,
                            'kanwil_id' => $employee['sms_kanwil_id'][0] ?? null,
                            'area_id' => $employee['sms_area_id'][0] ?? null,
                            'cabang_id' => $employee['sms_cabang_id'][0] ?? null,
                            'outlet_id' => $employee['sms_outlet_id'][0] ?? null,
                            'tgl_keluar' => $employee['tgl_keluar'],
                            'partner_id' => $employee['sms_partner_id'][0] ?? null,
                            'employee_category' => $employee['employee_categ'],
                            'identification_id' => $employee['identification_id'],
                            'registration_number' => $employee['registration_number'],
                            'customer_id' => $employee['customer_id'][0] ?? null,
                            'activity_type_id' => $employee['activity_type_id'][0] ?? null,
                            'department_id' => $employee['department_id'][0] ?? null,
                            'job_id' => $employee['job_id'][0] ?? null,
                            'address_id' => $employee['address_id'][0] ?? null,
                            'work_phone' => $employee['work_phone'],
                            'mobile_phone' => $employee['mobile_phone'],
                            'work_email' => $employee['work_email'],
                            'work_location' => $employee['work_location'],
                            'tz' => $employee['tz'],
                            'hr_presence_state' => $employee['hr_presence_state'],
                            'default_operating_unit_id' => $employee['default_operating_unit_id'][0] ?? null,
                            'create_date' => $employee['create_date'],
                            'write_date' => $employee['write_date']
                        ]);
                }
            } else {
                // Check if 'created_at' equals 'updated_at' and the 'id' doesn't exist
//                if ($employee['create_date'] === $employee['write_date']) {
                    try {
                        $createData = [
                            'odoo_employee_id' => $employee['id'], #int
                            'name' => $employee['name'], #string
                            'is_active' => $employee['active'] == 0 ? true : false,
                            'company_id' => $employee['company_id'][0] ?? null,
                            'gender' => !$employee['gender'] ? 'male' : $employee['gender'],
                            'country_id' => $employee['country_id'][0] ?? null,
                            'marital' => $employee['marital'], #enum[single,married]
                            'certificate' => $employee['certificate'], #string
                            'first_contract_date' => $employee['first_contract_date'] === false ? null : $employee['first_contract_date'], #date
                            'age' => $employee['age'], #int
                            'agama_id' => $employee['sms_agama_id'][0] ?? null, #int -> religions
                            'is_bpjs_kesehatan' => $employee['sms_is_bpjs_kesehatan'] == 0 ? true : false, #bool
                            'is_bpjs_ketenagakerjaan' => $employee['sms_is_bpjs_ketenagakerjaan'] == 0 ? true : false, #bool
                            'is_jamsostek' => $employee['sms_is_jamsostek'] == 0 ? true : false, #bool
                            'is_insurance' => $employee['sms_is_insurance'] == 0 ? true : false, #bool
                            'is_kta' => $employee['sms_is_kta'] == 0 ? true : false, #bool
                            'bpjs_kesehatan' => $employee['sms_bpjs_kesehatan'], #string
                            'bpjs_ketenagakerjaan' => $employee['sms_bpjs_ketenagakerjaan'], #string
                            'jamsostek' => $employee['sms_jamsostek'], #string
                            'insurance' => $employee['sms_insurance'], #string
                            'kartu_keluarga' => $employee['sms_kartu_keluarga'], #string
                            'kta' => $employee['sms_kta'], #string
                            'expired_kta' => $employee['sms_expired_kta'] == 0 ? true : false, #boolean
                            'ptkp_id' => $employee['sms_ptkp_id'][0] ?? null, #int -> ptkp
                            'baju_id' => $employee['sms_baju_id'], #int -> clothes
                            'celana_id' => $employee['sms_celana_id'], #int -> clothes
                            'sepatu_id' => $employee['sms_sepatu_id'], #int -> clothes
                            'darah_id' => $employee['sms_darah_id'], #int
                            'nickname' => $employee['sms_nick'], #string
                            'educational_id' => $employee['sms_educational_id'][0] ?? null, #int -> educations
                            'npwp' => $employee['sms_npwp'], #string
                            'corporate_id' => $employee['corporate_id'][0] ?? null,
                            'kanwil_id' => $employee['sms_kanwil_id'][0] ?? null,
                            'area_id' => $employee['sms_area_id'][0] ?? null,
                            'cabang_id' => $employee['sms_cabang_id'][0] ?? null,
                            'outlet_id' => $employee['sms_outlet_id'][0] ?? null,
                            'tgl_keluar' => $employee['tgl_keluar'],
                            'partner_id' => $employee['sms_partner_id'][0] ?? null,
                            'employee_category' => $employee['employee_categ'],
                            'identification_id' => $employee['identification_id'],
                            'registration_number' => $employee['registration_number'],
                            'customer_id' => $employee['customer_id'][0] ?? null,
                            'activity_type_id' => $employee['activity_type_id'][0] ?? null,
                            'department_id' => $employee['department_id'][0] ?? null,
                            'job_id' => $employee['job_id'][0] ?? null,
                            'address_id' => $employee['address_id'][0] ?? null,
                            'work_phone' => $employee['work_phone'],
                            'mobile_phone' => $employee['mobile_phone'],
                            'work_email' => $employee['work_email'],
                            'work_location' => $employee['work_location'],
                            'tz' => $employee['tz'],
                            'hr_presence_state' => $employee['hr_presence_state'],
                            'default_operating_unit_id' => $employee['default_operating_unit_id'][0] ?? null,
                            'create_date' => $employee['create_date'],
                            'write_date' => $employee['write_date']
                        ];
//                        dd($createData);
                        DB::table('employees')->insert($createData);
                    } catch (\InvalidArgumentException $e) {
                        dd($e);
                        $this->error($e->getMessage());
                    }

//                }
            }

            // Update syncs table
            DB::table('syncs')->updateOrInsert(
                [
                    'odoo_model' => 'hr.employee',
                    'odoo_record_id' => $employee['id'],
                ],
                [
                    'status' => 'synced',
                    'last_synced_at' => now(),
                ]
            );
        }

        $this->info('Employee data synced successfully!');
    }
}
