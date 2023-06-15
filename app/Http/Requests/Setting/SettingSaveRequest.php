<?php

namespace App\Http\Requests\Setting;

use App\Http\Requests\BaseRequest;

class SettingSaveRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $settingId = $this->route('setting') ? $this->route('setting')->id : null;

        return [
            'key' => 'required',
            'value' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'key '.self::REQUIRED,
            'value.required' => 'value '.self::REQUIRED,
            'key.unique' => 'key '.self::ALREADY_EXIST,
        ];
    }
}