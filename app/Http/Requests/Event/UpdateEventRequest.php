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
            'is_change_schedule' => ['nullable', 'boolean'],
            'repeat_type' => [
                'nullable',
                Rule::requiredIf($this->input('is_repeat', false) == true && $this->input('is_change_schedule', false) == true),
                Rule::in(Event::RepeatTypeDaily, Event::RepeatTypeWeekly, Event::RepeatTypeMonthly, Event::RepeatTypeYearly),
            ],
            'repeat_every' => [
                'nullable',
                Rule::requiredIf((bool) $this->input('is_repeat', false) == true && $this->input('is_change_schedule', false) == true),
                'numeric'
            ],
            'repeat_end_date' => [
                'nullable',
                Rule::requiredIf((bool) $this->input('is_repeat', false) == true && $this->input('is_change_schedule', false) == true),
                'date_format:Y-m-d'
            ],
            'repeat_days' => [
                'nullable',
                Rule::requiredIf($this->input('repeat_type') == Event::RepeatTypeWeekly || $this->input('repeat_type') == Event::RepeatTypeMonthly),
            ]
        ];
    }
}
