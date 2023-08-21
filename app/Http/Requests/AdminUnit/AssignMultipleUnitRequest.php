<?php

namespace App\Http\Requests\AdminUnit;

use Illuminate\Foundation\Http\FormRequest;

class AssignMultipleUnitRequest extends FormRequest
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
            'employee_id' => ['required'],
            'unit_relation_ids' => ['required', 'array']
        ];
    }
}
