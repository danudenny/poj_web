<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
                'guard_name' => 'sanctum',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'user_create',
                'guard_name' => 'sanctum',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'user_view',
                'guard_name' => 'sanctum',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'user_edit',
                'guard_name' => 'sanctum',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'user_delete',
                'guard_name' => 'sanctum',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'role_list',
                'guard_name' => 'sanctum',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'role_create',
                'guard_name' => 'sanctum',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'role_view',
                'guard_name' => 'sanctum',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'role_edit',
                'guard_name' => 'sanctum',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'role_delete',
                'guard_name' => 'sanctum',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        DB::table('permissions')->insert($permission);
    }
}
