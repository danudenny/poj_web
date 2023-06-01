<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class UpdateProfileRequest extends BaseRequest
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
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$this->user()->id,
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'name '.self::REQUIRED,
            'email.required' => 'email '.self::REQUIRED,
            'email.email' => 'email '.self::NOT_VALID,
            'email.unique' => 'email '.self::ALREADY_EXIST,
        ];
    }
}
