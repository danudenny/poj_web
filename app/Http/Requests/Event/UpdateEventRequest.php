<?php

namespace App\Http\Requests\Event;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
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
            'event_type' => ['nullable', Rule::in(Event::EventTypeAnggaran, Event::EventTypeNonAnggaran)],
            'image_url' => ['nullable'],
            'title' => ['nullable', 'max:225'],
            'description' => ['nullable'],
        ];
    }
}
