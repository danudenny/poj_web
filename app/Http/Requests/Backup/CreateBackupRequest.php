<?php

namespace App\Http\Requests\Backup;

use App\Models\Backup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBackupRequest extends FormRequest
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
            'unit_relation_id' => ['required'],
            'job_id' => ['required'],
            'shift_type' => ['required', Rule::in([Backup::TypeShift, Backup::TypeNonShift])],
            'timesheet_id' => ['nullable', Rule::requiredIf(($this->input('shift_type') == Backup::TypeShift))],
            'dates' => ['required', 'array'],
            'employee_ids' => ['required']
        ];
    }
}