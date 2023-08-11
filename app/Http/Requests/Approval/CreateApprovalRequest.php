<?php

namespace App\Http\Requests\Approval;

use Illuminate\Foundation\Http\FormRequest;

class CreateApprovalRequest extends FormRequest
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
            'unit_relation_id' => ['required', 'numeric'],
            'unit_level' => ['required', 'numeric'],
            'name' => ['required'],
            'approval_module_id' => ['required'],
            'approvers' => ['required', 'array'],
            'approvers.*.employee_id' => ['required'],
            'approvers.*.unit_relation_id' => ['required'],
            'approvers.*.unit_level' => ['required'],
        ];
    }
}
