<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property string $value
 *
 * @method static firstWhere(string $string, $id)
 * @method static find($id)
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];
}
