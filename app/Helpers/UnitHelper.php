<?php

namespace App\Helpers;

class UnitHelper
{
    public static function flattenUnits($units): array
    {
        $flattenedUnits = [];

        foreach ($units as $unit) {
            $flattenedUnit = [
                'id' => $unit['id'],
                'name' => $unit['name'],
                'value' => $unit['value'],
                'unit_level' => $unit['unit_level'],
                'parent_unit_id' => $unit['parent_unit_id'],
                'relation_id' => $unit['relation_id'],
                'create_date' => $unit['create_date'],
                'write_date' => $unit['write_date'],
                'is_active' => $unit['is_active'],
            ];

            $flattenedUnits[] = $flattenedUnit;

            if (!empty($unit['children'])) {
                $flattenedChildUnits = self::flattenUnits($unit['children']);

                foreach ($flattenedChildUnits as $childUnit) {
                    $flattenedUnits[] = $childUnit;
                }
            }
        }

        return $flattenedUnits;
    }
}
