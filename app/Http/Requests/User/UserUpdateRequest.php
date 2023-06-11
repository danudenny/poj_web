<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class UserUpdateRequest extends BaseRequest
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
            'email' => 'required|email',
            'roles' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'id '.self::REQUIRED,
            'name.required' => 'name '.self::REQUIRED,
            'email.required' => 'email '.self::REQUIRED,
            'email.email' => 'email '.self::NOT_VALID,
            'roles.required' => 'roles '.self::REQUIRED,
        ];
    }
}
