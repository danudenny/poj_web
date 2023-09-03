<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Attributes:
 * @property string $odoo_payslip_id
 *
 * Relations:
 * @property-read Employee $employee
 */
class TimesheetReportDetail extends Model
{
    use HasFactory;

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function generateERPAttribute() {
        $dataArr = [
            'total_work_day' => 'hari_calendar',
            'attendance_days' => 'kehadiran',
            'total_work_weekdays' => 'workday',
            'work_from_home_days' => 'dinas_wfh',
            'total_work_day_off' => 'holiday',
            'total_work_national_holiday' => 'libnas',
            'total_leave' => 'leave',
            'total_absent' => 'sakit_alpha',
            'total_backup' => 'lembur_backup',
            'total_overtime_first' => 'jumlah_lembur1',
            'total_overtime_second' => 'jumlah_lembur2',
            'total_overtime_public_holiday_first' => 'lembur_libnas_1_eksternal',
            'total_overtime_public_holiday_second' => 'lembur_libnas_2_eksternal',
            'total_overtime_public_holiday_third' => 'lembur_libnas_3_eksternal',
            'total_overtime_day_off_first' => 'lembur_libur_1_eksternal',
            'total_overtime_day_off_second' => 'lembur_libur_2_eksternal',
            'total_overtime_day_off_third' => 'lembur_libur_3_eksternal',
            'total_late_15' => 'lambat_15',
            'total_late_30' => 'lambat_30',
            'total_late_45' => 'lambat_45',
            'total_late_60' => 'lambat_60',
            'total_late_75' => 'lambat_75',
            'total_late_90' => 'lambat_90',
            'total_late_105' => 'lambat_105',
            'total_late_120' => 'lambat_120',
            'early_check_out_15' => 'pla_15',
            'early_check_out_30' => 'pla_30',
            'early_check_out_45' => 'pla_45',
            'early_check_out_60' => 'pla_60',
            'early_check_out_75' => 'pla_75',
            'early_check_out_90' => 'pla_90',
            'early_check_out_105' => 'pla_105',
            'early_check_out_120' => 'pla_120',
            'total_extended_day_off' => 'insentif_cb'
        ];

        $response = [];
        $skipValue = ['id', 'employee_id', 'timesheet_report_id', 'created_at', 'updated_at'];
        $valueArr = $this->attributesToArray();

        foreach ($valueArr as $key => $value) {
            if (!in_array($key, $skipValue)) {
                $response[$dataArr[$key]] = $value;
            }
        }

        return $response;
    }
}
