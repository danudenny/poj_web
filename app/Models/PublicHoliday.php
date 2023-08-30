<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property string $holiday_date
 * @property string $holiday_type
 * @property string $name
 * @property bool $is_shift
 * @property bool $is_non_shift
 */
class PublicHoliday extends Model
{
    use HasFactory;
}
