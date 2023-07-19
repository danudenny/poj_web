<?php

namespace App\Http\Requests\Backup;

use App\Models\Backup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BackupApprovalRequest extends FormRequest
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
            'status' => ['required', Rule::in([Backup::StatusApproved, Backup::StatusRejected])],
            'notes' => ['nullable', Rule::requiredIf($this->input('status') == Backup::StatusRejected)]
        ];
    }
}
