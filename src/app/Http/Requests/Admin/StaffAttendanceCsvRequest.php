<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StaffAttendanceCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'ym'      => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'ユーザーが指定されていません',
            'user_id.exists'   => 'ユーザーが見つかりません',
            'ym.required'      => '対象月を指定してください',
            'ym.regex'         => '対象月はYYYY-MM形式で指定してください',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'ユーザー',
            'ym'      => '対象月',
        ];
    }
}
