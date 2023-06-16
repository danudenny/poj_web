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
            'start_time' => 'required',
            'end_time' => 'required',
            'is_active' => 'boolean',
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
