<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $units = [
            ['name' => 'PT. Pesonna Optima Jasa',
                'value' => 'POJ',
                'unit_level' => 1,
                'parent_unit_id' => NULL,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'KANWIL MEDAN',
                'value' => 'POJ',
                'unit_level' => 3,
                'parent_unit_id' => 1,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'KANWIL PEKANBARU',
                'value' => 'POJ',
                'unit_level' => 3,
                'parent_unit_id' => 1,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'KANWIL PALEMBANG',
                'value' => 'POJ',
                'unit_level' => 3,
                'parent_unit_id' => 1,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'AREA BANDA ACEH',
                'value' => 'POJ',
                'unit_level' => 4,
                'parent_unit_id' => 2,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'AREA MEDAN 1',
                'value' => 'POJ',
                'unit_level' => 4,
                'parent_unit_id' => 2,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'AREA MEDAN 2',
                'value' => 'POJ',
                'unit_level' => 4,
                'parent_unit_id' => 2,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'AREA PEKANBARU',
                'value' => 'POJ',
                'unit_level' => 4,
                'parent_unit_id' => 3,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'AREA BATAM',
                'value' => 'POJ',
                'unit_level' => 4,
                'parent_unit_id' => 3,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'PT Pegadaian',
                'value' => 'PGDN',
                'unit_level' => 2,
                'parent_unit_id' => NULL,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'PT Pegadaian - Kantor Wilayah IV Balikpapan',
                'value' => 'PGDN',
                'unit_level' => 3,
                'parent_unit_id' => 10,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'PT Djabesdepo Fortuna Raya',
                'value' => 'DFM',
                'unit_level' => 2,
                'parent_unit_id' => NULL,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'PT Djabesdepo Fortuna Raya - Medan',
                'value' => 'DFM',
                'unit_level' => 5,
                'parent_unit_id' => 12,
                'create_date' => now(),
                'write_date' => now()],
            ['name' => 'PT Djabesdepo Fortuna Raya - Rental Kendaraan',
                'value' => 'DFM',
                'unit_level' => 6,
                'parent_unit_id' => 12,
                'create_date' => now(),
                'write_date' => now()],
        ];

        Unit::insert($units);
    }
}
