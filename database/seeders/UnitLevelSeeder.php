<?php

namespace Database\Seeders;

use App\Models\UnitLevel;
use Illuminate\Database\Seeder;

class UnitLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $unitLevels = [
            ['name' => 'Company',
             'desc' => 'company',
             'value' => 1,
             'created_at' => now(),
             'updated_at' => now()],
            ['name' => 'Corporate',
                'desc' => 'corporate',
                'value' => 2,
                'created_at' => now(),
                'updated_at' => now()],
            ['name' => 'Regional',
                'desc' => 'regional',
                'value' => 3,
                'created_at' => now(),
                'updated_at' => now()],
            ['name' => 'Area',
                'desc' => 'area',
                'value' => 4,
                'created_at' => now(),
                'updated_at' => now()],
            ['name' => 'Cabang',
                'desc' => 'cabang',
                'value' => 5,
                'created_at' => now(),
                'updated_at' => now()],
            ['name' => 'Outlet',
                'desc' => 'outlet',
                'value' => 6,
                'created_at' => now(),
                'updated_at' => now()]
        ];

        UnitLevel::insert($unitLevels);
    }
}
