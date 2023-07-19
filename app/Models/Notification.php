<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $employee_id
 * @property string $title
 * @property string $sub_title
 * @property string $description
 * @property string $reference_type
 * @property int|null $reference_id
 * @property bool $is_read
 */
class Notification extends Model
{
    use HasFactory;

    const ReferenceBackup = "backup";
    const ReferenceOvertime = "overtime";
}
