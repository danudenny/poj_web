<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

/**
 * Attributes:
 * @property-read int $id
 * @property int $unit_relation_id
 * @property int $odoo_job_id
 * @property int $parent_unit_job_id
 *
 * Relations:
 * @property-read Unit $unit
 * @property-read Job $job
 * @property-read UnitHasJob $parent
 * @method static updateOrInsert(array $array, array $array1)
 */
class UnitHasJob extends Model
{
    use HasFactory;
    use QueriesExpressions;

    protected $appends = [
        'job_name'
    ];

    protected $with = [
        'unit',
        'job'
    ];

    public function getJobNameAttribute(): string
    {
        return $this->job->name;
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_relation_id', 'relation_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'odoo_job_id', 'odoo_job_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(UnitHasJob::class, 'parent_unit_job_id');
    }

    public static function getHierarchicalData($relationId = null): array
    {
        $rawData = self::query()
            ->whereHas('unit', function ($query) use ($relationId) {
                $query->where('relation_id', '=', $relationId);
            })
            ->orderBy('id', 'ASC')
            ->get();

        return self::flattenData($rawData);
    }

    public static function flattenData($data, $parentId = null): array
    {
        $flattened = [];

        foreach ($data as $item) {
            if ($item->parent_unit_job_id == $parentId) {
                $flattenedItem = [
                    'id' => $item->id,
                    'name' => $item->job->name,
                    'title' => $item->unit->name,
                ];

                if (!is_null($item->parent_unit_job_id)) {
                    $flattenedItem['pid'] = $item->parent_unit_job_id;
                }

                $flattened[] = $flattenedItem;
                $flattened = array_merge($flattened, self::flattenData($data, $item->id));
            }
        }

        return $flattened;
    }
}
