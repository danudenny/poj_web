<?php

namespace App\Http\Requests\EmployeeTimesheet;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeTimesheetScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'timesheet_date' => ['required', 'date_format:Y-m-d'],
            'timesheet' => ['required'],
            'timesheet.id' => ['required'],
            'timesheet.start_time' => ['required', 'date_format:H:i:s'],
            'timesheet.end_time' => ['required', 'date_format:H:i:s'],
            'unit_relation_id' => ['required']
        ];
    }
}
