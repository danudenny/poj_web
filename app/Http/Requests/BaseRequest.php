<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaseRequest extends FormRequest
{
    public const REQUIRED = 'tidak boleh kosong';
    public const NOT_VALID = 'tidak valid';
    public const ALREADY_EXIST = 'sudah digunakan';
}
