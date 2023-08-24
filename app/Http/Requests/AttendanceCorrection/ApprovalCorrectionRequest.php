<?php

namespace App\Http\Requests\AttendanceCorrection;

use App\Models\AttendanceCorrectionApproval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApprovalCorrectionRequest extends FormRequest
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
            'status' => ['required', Rule::in(AttendanceCorrectionApproval::StatusApproved, AttendanceCorrectionApproval::StatusRejected)],
            'notes' => [Rule::requiredIf($this->input('status') == AttendanceCorrectionApproval::StatusRejected)]
        ];
    }
}
