<?php

namespace App\Http\Requests\AttendanceCorrection;

use Illuminate\Foundation\Http\FormRequest;

class CreateCorrectionRequest extends FormRequest
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
            'employee_attendance_id' => ['required'],
            'check_in_time' => ['required', 'date_format:H:i:s'],
            'check_out_time' => ['required', 'date_format:H:i:s'],
            'notes' => ['required'],
            'file' => ['nullable']
        ];
    }
}
