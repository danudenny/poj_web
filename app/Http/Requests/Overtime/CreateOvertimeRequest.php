<?php

namespace App\Http\Requests\Overtime;

use App\Models\Overtime;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOvertimeRequest extends FormRequest
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
            'unit_relation_id' => ['required'],
            'job_id' => ['required'],
            'dates' => ['required', 'array'],
            'employee_ids' => ['required'],
            'notes' => ['required'],
            'image_url' => ['nullable'],
            'request_type' => ['required', Rule::in([Overtime::RequestTypeAssignment, Overtime::RequestTypeRequest])]
        ];
    }

    public function messages()
    {
        return [
            'unit_relation_id.required' => 'Unit Kosong, Mohon Pilih Unit',
            'job_id.required' => 'Pekerjaan Kosong, Mohon Pilih Pekerjaan',
            'dates.required' => 'Tanggal Kosong, Mohon Masukkan Tanggal',
            'employee_ids.required' => 'Pegawai Kosong, Mohon Pilih Pegawai',
            'notes.required' => 'Catatan Kosong, Mohon Masukkan Catatan',
        ];
    }
}
