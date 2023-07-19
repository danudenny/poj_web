<?php

namespace App\Http\Requests\Event;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEventRequest extends FormRequest
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
            'event_type' => ['required', Rule::in(Event::EventTypeAnggaran, Event::EventTypeNonAnggaran)],
            'image_url' => ['required'],
            'title' => ['required', 'max:225'],
            'description' => ['required'],
            'latitude' => ['required', Rule::requiredIf(Event::LocationTypeExternal)],
            'longitude' => ['required', Rule::requiredIf(Event::LocationTypeExternal)],
            'location_type' => ['required', Rule::in(Event::LocationTypeInternal, Event::LocationTypeExternal)],
            'address' => ['required'],
            'date_event' => ['required', 'date'],
            'time_event' => ['required', 'date_format:H:i'],
            'is_need_absence' => ['required', 'boolean'],
            'is_repeat' => ['required', 'boolean'],
            'event_attendances' => ['required', 'array'],
            'repeat_type' => [
                'nullable',
                Rule::requiredIf($this->input('is_repeat', false) == true),
                Rule::in(Event::RepeatTypeDaily, Event::RepeatTypeWeekly, Event::RepeatTypeMonthly, Event::RepeatTypeYearly),
            ],
            'repeat_every' => [
                'nullable',
                Rule::requiredIf((bool) $this->input('is_repeat', false) == true),
                'numeric'
            ],
            'repeat_end_date' => [
                'nullable',
                Rule::requiredIf((bool) $this->input('is_repeat', false) == true),
                'date_format:Y-m-d'
            ],
            'repeat_days' => [
                'nullable',
                Rule::requiredIf($this->input('repeat_type') == Event::RepeatTypeWeekly || $this->input('repeat_type') == Event::RepeatTypeMonthly),
            ]
        ];
    }
}
