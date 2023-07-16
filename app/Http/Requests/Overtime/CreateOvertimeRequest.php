<?php

namespace App\Http\Requests\Overtime;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOvertimeRequest extends FormRequest
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
            'start_datetime' => ['required', 'date_format:Y-m-d H:i:s'],
            'end_datetime' => ['required', 'date_format:Y-m-d H:i:s'],
            'notes' => ['required'],
            'image_url' => ['nullable', 'url'],
            'employees' => ['required', 'array'],
            'unit_relation_id' => [
                Rule::requiredIf(function() {
                    /**
                     * @var User $user
                     */
                    $user = request()->user();

                    return !$user->inRoleLevel([Role::RoleStaff]);
                })
            ]
        ];
    }
}
