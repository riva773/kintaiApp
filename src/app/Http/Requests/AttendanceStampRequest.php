<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;
use App\Models\Attendance;

class AttendanceStampRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:clock_in,clock_out,break_in,break_out'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => '不正な操作です（action 不足）。',
            'action.in'       => '不正な操作です（実行できない action です）。',
        ];
    }

    protected function prepareForValidation(): void
    {
        $raw = (string) $this->input('action', '');

        $map = [
            'work-start'  => 'clock_in',
            'clock-in'    => 'clock_in',
            '出勤'         => 'clock_in',

            'work-end'    => 'clock_out',
            'clock-out'   => 'clock_out',
            '退勤'         => 'clock_out',

            'break-start' => 'break_in',
            'break-in'    => 'break_in',
            '休憩入'        => 'break_in',

            'break-end'   => 'break_out',
            'break-out'   => 'break_out',
            '休憩戻'        => 'break_out',
        ];

        $normalized = $map[$raw] ?? $raw;
        $this->merge(['action' => $normalized]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $userId = auth()->id();
            $today  = Carbon::now()->toDateString();

            $attendance = Attendance::with(['breaks' => function ($q) {
                $q->orderByDesc('break_started_at')->orderByDesc('id');
            }])
                ->where('user_id', $userId)
                ->whereDate('work_date', $today)
                ->latest('id')
                ->first();

            $action = $this->input('action');

            $status = 'not_working';
            $hasOpenBreak = false;

            if ($attendance) {
                $status = $attendance->status ?? 'not_working';
                $hasOpenBreak = $attendance->breaks()->whereNull('break_ended_at')->exists();
            }

            if ($status === 'not_working' && $action !== 'clock_in') {
                $v->errors()->add('action', '出勤前に実行できない操作です。');
                return;
            }

            if ($status === 'working' && !in_array($action, ['break_in', 'clock_out'], true)) {
                $v->errors()->add('action', '出勤中に実行できない操作です。');
                return;
            }

            if ($status === 'on_break' && $action !== 'break_out') {
                $v->errors()->add('action', '休憩中に実行できない操作です。');
                return;
            }

            if ($action === 'clock_out' && $hasOpenBreak) {
                $v->errors()->add('action', '休憩を終了してから退勤してください。');
            }
        });
    }
}
