<?php

namespace App\Http\Requests\EmployeeTimesheet;

use App\Http\Requests\BaseRequest;

class UpdateEmployeeTimesheetRequest extends BaseRequest
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
            'id' => 'required',
            'name' => 'string',
            'is_active' => 'boolean',
            'start_time' => 'string',
            'end_time' => 'string',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'ID '.self::REQUIRED,
            'name.string' => 'Name '.self::STRING,
            'is_active.boolean' => 'Is Active '.self::BOOLEAN,
            'start_time.string' => 'Start Time '.self::STRING,
            'end_time.string' => 'End Time '.self::STRING,
        ];
    }
}
