<?php

namespace App\Http\Requests\Backup;

use Illuminate\Foundation\Http\FormRequest;

class BackupCheckOutRequest extends FormRequest
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
            'latitude' => ['required'],
            'longitude' => ['required'],
            'image_url' => ['required']
        ];
    }
}
