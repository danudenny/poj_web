<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Rememberable\Rememberable;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property string|null $fcm_token
 *
 * Relations:
 * @property-read Employee $employee
 * @method static firstWhere(string $string, $id)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, Rememberable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'authkey',
        'is_active',
        'employee_id',
        'avatar',
        'fcm_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pivot'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getIsInRepresentativeUnitAttribute() {
        $employee = $this->employee;

        return Unit::query()
            ->where('relation_id', '=', $employee->default_operating_unit_id)
            ->whereIn('unit_level', [Unit::UnitLevelOperatingUnit])
            ->exists();
    }

    public function getIsInCentralUnitAttribute() {
        $employee = $this->employee;

        return Unit::query()
            ->where('relation_id', '=', $employee->default_operating_unit_id)
            ->whereIn('unit_level', [Unit::UnitLevelPOJ])
            ->exists();
    }

    /**
     * @param array $roleLevels
     * @return bool
     */
    public function inRoleLevel(array $roleLevels): bool {
        /**
         * @var Role[] $roles
         */
        $roles = $this->roles;

        foreach ($roles as $role) {
            if (in_array($role->role_level, $roleLevels)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated:
     * Move to isRequestedRoleLevel func in service layer
     */
    public function isHighestRole(string $roleLevel): bool {
        return $this->getHighestRole()->role_level == $roleLevel;
    }

    /**
     * @return Role|null
     */
    public function getHighestRole() {
        /**
         * @var Role[] $roles
         */
        $roles = $this->roles;

        /**
         * @var Role|null $highestPriorityRole
         */
        $highestPriorityRole = null;

        foreach ($roles as $role) {
            if ($highestPriorityRole === null || $role->priority < $highestPriorityRole->priority) {
                $highestPriorityRole = $role;
            }
        }

        return $highestPriorityRole;
    }

    public function employee(): BelongsTo
    {
        return $this->BelongsTo(Employee::class, 'employee_id')->with(['department', 'team']);
    }

    public function department() {
        return $this->employee->department;
    }

    public function approvals(): BelongsToMany
    {
        return $this->belongsToMany(Approval::class, 'approval_users');
    }

    public function hasPermission($permission)
    {
        return $this->roles->flatMap->permissions->contains($permission);
    }

    public function hasPermissionName(string $permissionName): bool {
        foreach ($this->roles->flatMap->permissions as $permission) {
            if ($permission->name == $permissionName) {
                return true;
            }
        }

        return false;
    }

    public function listOperatingUnitIDs(): array {
        $operatingUnitIDs = [];

        /**
         * @var OperatingUnitUser[] $operatingUnitUsers
         */
        $operatingUnitUsers = OperatingUnitUser::query()
            ->where('user_id', '=', $this->id)
            ->get();

        foreach ($operatingUnitUsers as $operatingUnitUser) {
            $corporate = $operatingUnitUser->operatingUnitCorporate;

            foreach ($corporate->operatingUnitDetails as $operatingUnitDetail) {
                $operatingUnitIDs[] = $operatingUnitDetail->unit_relation_id;
            }
        }

        return $operatingUnitIDs;
    }
}
