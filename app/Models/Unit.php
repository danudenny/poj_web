<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Staudenmeir\LaravelCte\Eloquent\QueriesExpressions;

/**
 * Attributes:
 * @property-read int $id
 * @property string $name
 * @property string $parent_unit_id
 * @property string $relation_id
 * @property int $unit_level
 * @property float $lat
 * @property float $long
 * @property float $radius
 * @property int $early_buffer
 * @property int $late_buffer
 * @property string $code
 *
 * Relations:
 * @property-read OperatingUnitCorporate[] $operatingUnitCorporates
 * @property-read OperatingUnitDetail $operatingUnitDetail
 * @method static where(string $string, $unit_id)
 */
class Unit extends Model
{
    use HasFactory;
    use QueriesExpressions;

    const UnitLevelPOJ = 1;
    const UnitLevelOperatingUnit = 2;
    const UnitLevelCorporate = 3;
    const UnitLevelKanwil = 4;
    const UnitLevelArea = 5;
    const UnitLevelCabang = 6;
    const UnitLevelOutlet = 7;

    protected $fillable = [
        'name',
        'value',
        'unit_level',
        'parent_unit_id',
        'is_active'
    ];

    protected $appends = [
        'formatted_name'
    ];

    protected $casts = [
        'create_date'  => 'date:Y-m-d',
        'write_date'  => 'date:Y-m-d',
    ];

    public function getFormattedNameAttribute() {
        return sprintf("[%s] %s", $this->code, $this->name);
    }

    public function getTotalManagedOperatingUnitAttribute() {
        $total = OperatingUnitDetail::query()
            ->join('operating_unit_corporates', 'operating_unit_corporates.id', '=', 'operating_unit_details.operating_unit_corporate_id')
            ->where('operating_unit_corporates.operating_unit_relation_id', '=', $this->relation_id)
            ->count();

        return $total;
    }

    public function getTotalChildAttribute() {
        return Unit::query()->where('parent_unit_id', '=', $this->relation_id)->count();
    }

    public function getNameWithCorporateAttribute() {
        return $this->name;
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(UnitLevel::class, 'unit_level', 'value');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_unit_id', 'relation_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_unit_id', 'relation_id');
    }

    public function workLocations(): HasMany
    {
        return $this->hasMany(WorkLocation::class, 'reference_id', 'id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'id', 'unit_key');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_has_teams')
            ->withPivot('team_id', 'unit_level');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'department_has_teams')
            ->withPivot('team_id', 'unit_level');
    }

    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'unit_jobs', 'unit_id', 'job_id', 'relation_id', 'id')
            ->withPivot('is_camera', 'is_upload', 'is_reporting', 'is_mandatory_reporting', 'type', 'total_reporting', 'total_normal', 'total_backup', 'total_overtime', 'reporting_names');
    }

    public function jobHasUnits(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'unit_has_jobs', 'odoo_job_id', 'unit_relation_id', 'odoo_job_id', 'relation_id');
    }

    public function operatingUnitDetail()
    {
        return $this->hasOne(OperatingUnitDetail::class, 'unit_relation_id', 'relation_id');
    }

    public function operatingUnitCorporates()
    {
        return $this->hasMany(OperatingUnitCorporate::class, 'operating_unit_relation_id', 'relation_id');
    }
}
