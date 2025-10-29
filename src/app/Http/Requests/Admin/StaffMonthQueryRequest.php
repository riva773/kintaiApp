<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StaffMonthQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ym' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'ym.required' => '対象月を指定してください',
            'ym.regex'    => '対象月はYYYY-MM形式で指定してください',
        ];
    }

    public function attributes(): array
    {
        return [
            'ym' => '対象月',
        ];
    }
}
