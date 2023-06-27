<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeUnit extends Model
{
    use HasFactory;

    protected $table = 'employee_units';

    public function employee(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
