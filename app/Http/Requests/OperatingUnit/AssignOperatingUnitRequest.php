<?php

namespace App\Http\Requests\OperatingUnit;

use Illuminate\Foundation\Http\FormRequest;

class AssignOperatingUnitRequest extends FormRequest
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
            'representative_office_id' => ['required'],
            'corporates' => ['required', 'array'],
            'corporates.*.unit_relation_id' => ['required', 'numeric'],
            'corporates.*.kanwils' => ['required', 'array']
        ];
    }
}
