<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
//        $this->call(PermissionSeeder::class);
//        $this->call(PermissionSeeder::class);
//        $this->call(SettingSeeder::class);
    }
}
