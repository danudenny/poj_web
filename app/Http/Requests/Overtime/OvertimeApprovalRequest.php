<?php

namespace App\Http\Requests\Overtime;

use App\Models\OvertimeHistory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OvertimeApprovalRequest extends FormRequest
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
            'status' => [
                'required',
                Rule::in(OvertimeHistory::TypeApproved, OvertimeHistory::TypeRejected)
            ],
            'notes' => [
                Rule::requiredIf($this->input('status') == OvertimeHistory::TypeRejected)
            ]
        ];
    }
}
