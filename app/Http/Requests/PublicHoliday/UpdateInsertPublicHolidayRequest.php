<?php

namespace App\Http\Requests\PublicHoliday;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInsertPublicHolidayRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d'],
            'type' => ['nullable'],
            'name' => ['required'],
            'is_shift' => ['required'],
            'is_non_shift' => ['required']
        ];
    }
}
