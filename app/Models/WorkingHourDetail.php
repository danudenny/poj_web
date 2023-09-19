<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property-read int $id
 * @property int $odoo_working_hour_id
 * @property int $odoo_working_hour_detail_id
 * @property string $name
 * @property string $day_period
 * @property float $hour_from
 * @property float $hour_to
 * @property int $day_of_week
 */
class WorkingHourDetail extends Model
{
    use HasFactory;

    protected $appends = [
        "hour_from_string",
        "hour_to_string",
        "day_of_week_string"
    ];

    public function getHourFromStringAttribute() {
        $hour = floor($this->hour_from);
        $minutes = 60 * ($this->hour_from - $hour);

        return sprintf("%02d:%02d", $hour, $minutes);
    }

    public function getHourToStringAttribute() {
        $hour = floor($this->hour_to);
        $minutes = 60 * ($this->hour_to - $hour);

        return sprintf("%02d:%02d", $hour, $minutes);
    }

    public function getDayOfWeekStringAttribute() {
        return match ($this->day_of_week) {
            0 => "Senin",
            1 => "Selasa",
            2 => "Rabu",
            3 => "Kamis",
            4 => "Jumat",
            5 => "Sabtu",
            6 => "Minggu"
        };
    }
}
