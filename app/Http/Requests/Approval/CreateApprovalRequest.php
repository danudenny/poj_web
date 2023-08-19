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
            'unit_relation_id' => ['required'],
            'unit_level' => ['required'],
            'name' => ['required'],
            'approval_module_id' => ['required'],
            'department_id' => ['nullable', 'numeric'],
            'team_id' => ['nullable', 'numeric'],
            'odoo_job_id' => ['nullable', 'numeric'],
            'approvers' => ['required', 'array'],
            'approvers.*.employee_id' => ['required'],
            'approvers.*.unit_relation_id' => ['required'],
            'approvers.*.unit_level' => ['required'],
            'approvers.*.department_id' => ['nullable', 'numeric'],
            'approvers.*.team_id' => ['nullable', 'numeric'],
            'approvers.*.odoo_job_id' => ['nullable', 'numeric'],
        ];
    }
}
