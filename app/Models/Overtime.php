<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $requestor_employee_id
 * @property string|null $approved_at
 * @property string $date_overtime
 * @property string $start_time
 * @property string $end_time
 * @property string $notes
 * @property string|null $image_url
 * @property float $location_lat
 * @property float $location_long
 */
class Overtime extends Model
{
    use HasFactory;
}
