<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseRequest;

class RoleSaveRequest extends BaseRequest
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
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'name '.self::REQUIRED,
            'permission.required' => 'permission '.self::REQUIRED,
            'name.unique' => 'role name '.self::ALREADY_EXIST,
        ];
    }
}
