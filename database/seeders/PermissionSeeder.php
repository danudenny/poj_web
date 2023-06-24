<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = [
            [
                'name' => 'user_list',
            ],
            [
                'name' => 'user_create',
            ],
            [
                'name' => 'user_view',
            ],
            [
                'name' => 'user_edit',
            ],
            [
                'name' => 'role_list',
            ],
            [
                'name' => 'role_create',
            ],
            [
                'name' => 'role_view',
            ],
            [
                'name' => 'role_edit',
            ],
            [
                'name' => 'role_delete',
            ],
            [
                'name' => 'employee_detail',
            ],
            [
                'name' => 'dashboard',
            ],
            [
                'name' => 'permission_list',
            ],
            [
                'name' => 'master_data',
            ],
            [
                'name' => 'employee_list',
            ],
            [
                'name' => 'working_area',
            ],
            [
                'name' => 'corporate_list',
            ],
            [
                'name' => 'kanwil_list',
            ],
            [
                'name' => 'area_list',
            ],
            [
                'name' => 'cabang_list',
            ],
            [
                'name' => 'outlet_list',
            ],
            [
                'name' => 'attendance_list',
            ],
            [
                'name' => 'assesment_list',
            ],
            [
                'name' => 'content_list',
            ],
            [
                'name' => 'report_list',
            ],
            [
                'name' => 'configuration_list',
            ],
            [
                'name' => 'general_setting',
            ]
        ];

        foreach ($permission as $data) {
            Permission::create(
              ['name' => $data['name'],
               'guard_name' => 'sanctum']);
        }
        //DB::table('permissions')->insert($permission);
    }
}
