<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'decision'      => ['required', 'in:approve,reject'],
            'admin_remarks' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required'      => '承認または却下を選択してください。',
            'decision.in'            => '不正な操作です。',
            'admin_remarks.required' => '備考を入力してください。',
            'admin_remarks.max'      => '備考は255文字以内で入力してください。',
        ];
    }

    public function attributes(): array
    {
        return [
            'decision'      => '承認可否',
            'admin_remarks' => '備考',
        ];
    }
}
