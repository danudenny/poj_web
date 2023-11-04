<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property string $leave_name
 * @property string $leave_code
 * @property string $leave_type
 */
class MasterLeave extends Model
{
    use HasFactory;

    const CodeSickNonSKD = "I-002";

    const TypePermit = "permit";
    const TypeLeave = "leave";
}
