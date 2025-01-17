<?php

namespace App\Http\Requests\OperatingUnit;

use Illuminate\Foundation\Http\FormRequest;

class RemoveUserRequest extends FormRequest
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
            'user_id' => ['required'],
            'operating_unit_corporate_id' => ['required']
        ];
    }
}
