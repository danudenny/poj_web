<?php

namespace App\Http\Requests\Timesheet;

use App\Models\EmployeeTimesheet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTimesheetRequest extends FormRequest
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
            'name' => ['required'],
            'shift_type' => ['required', Rule::in([EmployeeTimesheet::TypeShift, EmployeeTimesheet::TypeNonShift])],
            'start_time' => [Rule::requiredIf($this->input('shift_type') == EmployeeTimesheet::TypeShift)],
            'end_time' => [Rule::requiredIf($this->input('shift_type') == EmployeeTimesheet::TypeShift)],
            'days' => ['nullable', Rule::requiredIf($this->input('shift_type') == EmployeeTimesheet::TypeNonShift), 'array']
        ];
    }
}
