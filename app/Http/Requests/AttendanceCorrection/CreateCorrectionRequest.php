<?php

namespace App\Http\Requests\AttendanceCorrection;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'check_in_time' => ['required', 'date_format:H:i:s'],
            'check_out_time' => ['required', 'date_format:H:i:s'],
            'notes' => ['required'],
            'file' => ['nullable'],
            'correction_date' => ['required', 'date_format:Y-m-d'],
            'correction_type' => [
                'required',
                Rule::in(AttendanceCorrectionRequest::TypeNormal, AttendanceCorrectionRequest::TypeBackup, AttendanceCorrectionRequest::TypeOvertime)
            ],
            'reference_type' => ['required'],
            'reference_id' => ['required']
        ];
    }
}
