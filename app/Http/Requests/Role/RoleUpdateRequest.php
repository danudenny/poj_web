<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseRequest;

class RoleUpdateRequest extends BaseRequest
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
            'id' => 'required',
            'name' => 'required',
            'permission' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'id '.self::REQUIRED,
            'name.required' => 'name '.self::REQUIRED,
            'permission.required' => 'permission '.self::REQUIRED,
        ];
    }
}
