<?php

namespace App\Http\Requests\EmployeeTimesheet;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeTimesheetRequest extends FormRequest
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
        return [
            'name' => 'required|string',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'is_active' => 'boolean',
            'days.*.day' => 'required_with:days.*.start_time,days.*.end_time',
            'days.*.start_time' => 'nullable|required_with:days.*.end_time',
            'days.*.end_time' => 'nullable|required_with:days.*.start_time',
        ];
    }

    public  function messages(): array
    {
        return [
            'name.required' => 'Timesheet Name is required',
            'start_time.required' => 'Start Time is required',
            'end_time.required' => 'End Time is required',
            'is_active.boolean' => 'Is Active must be boolean',
        ];
    }
}
