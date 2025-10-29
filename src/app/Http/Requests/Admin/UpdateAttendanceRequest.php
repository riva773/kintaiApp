<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;
use App\Models\Attendance;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work-start' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'work-end'   => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'breaks.*.start' => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'breaks.*.end'   => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'clock_in_at'     => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'clock_out_at'    => ['nullable', 'regex:/^\d{2}:\d{2}$/'],
            'remarks'         => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'work-start.regex'        => '出勤時間が不適切な値です（HH:MM）',
            'work-end.regex'          => '退勤時間が不適切な値です（HH:MM）',
            'breaks.*.start.regex'    => '休憩開始が不適切な値です（HH:MM）',
            'breaks.*.end.regex'      => '休憩終了が不適切な値です（HH:MM）',
            'clock_in_at.regex'       => '出勤時間が不適切な値です（HH:MM）',
            'clock_out_at.regex'      => '退勤時間が不適切な値です（HH:MM）',
            'remarks.required'        => '備考は必須です（変更理由を記載してください）',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $attendanceId = $this->route('id');
            $attendance = Attendance::find($attendanceId);
            if (!$attendance) {
                $v->errors()->add('id', '勤怠が見つかりませんでした。');
                return;
            }

            $base = $attendance->work_date ? Carbon::parse($attendance->work_date)->startOfDay() : null;
            $toCarbon = function (?string $hhmm) use ($base) {
                if (!$base || !$hhmm) return null;
                [$h, $m] = array_map('intval', explode(':', $hhmm));
                return $base->copy()->setTime($h, $m, 0);
            };

            $inStr  = $this->input('clock_in_at')  ?? $this->input('work-start');
            $outStr = $this->input('clock_out_at') ?? $this->input('work-end');

            $cin  = $toCarbon($inStr);
            $cout = $toCarbon($outStr);

            if ($cin && $cout && $cin->gte($cout)) {
                $v->errors()->add('work-start', '出勤は退勤より前である必要があります');
                $v->errors()->add('work-end',   '退勤は出勤より後である必要があります');
            }

            $rows = collect($this->input('breaks', []))
                ->map(fn($r) => [
                    'start_raw' => $r['start'] ?? null,
                    'end_raw'   => $r['end'] ?? null,
                ]);

            foreach ($rows as $idx => $r) {
                $hasStart = !empty($r['start_raw']);
                $hasEnd   = !empty($r['end_raw']);
                if ($hasStart xor $hasEnd) {
                    $v->errors()->add("breaks.$idx.start", '休憩は開始と終了を両方入力してください');
                    $v->errors()->add("breaks.$idx.end",   '休憩は開始と終了を両方入力してください');
                }
            }

            $normalized = $rows
                ->filter(fn($r) => !empty($r['start_raw']) && !empty($r['end_raw']))
                ->map(fn($r) => [
                    'start' => $toCarbon($r['start_raw']),
                    'end'   => $toCarbon($r['end_raw']),
                ])
                ->values();

            foreach ($normalized as $idx => $r) {
                if ($r['start'] && $r['end'] && $r['start']->gte($r['end'])) {
                    $v->errors()->add("breaks.$idx.start", '休憩の開始は終了より前である必要があります');
                    $v->errors()->add("breaks.$idx.end",   '休憩の終了は開始より後である必要があります');
                }
            }

            if ($cin && $cout) {
                foreach ($normalized as $idx => $r) {
                    if ($r['start'] && $r['start']->lt($cin)) {
                        $v->errors()->add("breaks.$idx.start", '休憩開始が勤務時間外です');
                    }
                    if ($r['end'] && $r['end']->gt($cout)) {
                        $v->errors()->add("breaks.$idx.end", '休憩終了が勤務時間外です');
                    }
                }
            }

            $sorted = $normalized->sortBy('start')->values();
            for ($i = 1; $i < $sorted->count(); $i++) {
                $prev = $sorted[$i - 1];
                $curr = $sorted[$i];
                if ($prev['end'] && $curr['start'] && $prev['end']->gt($curr['start'])) {
                    $v->errors()->add("breaks." . ($i - 1) . ".end", '休憩時間が他の休憩と重複しています');
                    $v->errors()->add("breaks.$i.start",             '休憩時間が他の休憩と重複しています');
                }
            }
        });
    }
}
