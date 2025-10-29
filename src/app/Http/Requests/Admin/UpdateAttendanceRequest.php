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
            'work-start'        => ['nullable', 'date_format:H:i'],
            'work-end'          => ['nullable', 'date_format:H:i'],

            'clock_in_at'       => ['nullable', 'date_format:H:i'],
            'clock_out_at'      => ['nullable', 'date_format:H:i'],

            'breaks'            => ['nullable', 'array'],
            'breaks.*.start'    => ['nullable', 'date_format:H:i'],
            'breaks.*.end'      => ['nullable', 'date_format:H:i'],

            'remarks'           => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'work-start.date_format'        => '出勤時間もしくは退勤時間が不適切な値です',
            'work-end.date_format'          => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_in_at.date_format'       => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out_at.date_format'      => '出勤時間もしくは退勤時間が不適切な値です',

            'breaks.*.start.date_format'    => '休憩時間が不適切な値です',
            'breaks.*.end.date_format'      => '休憩時間が不適切な値です',

            'remarks.required'              => '備考を記入してください',
            'remarks.max'                   => '備考を記入してください',
        ];
    }

    public function attributes(): array
    {
        return [
            'work-start'         => '出勤時間',
            'work-end'           => '退勤時間',
            'clock_in_at'        => '出勤時間',
            'clock_out_at'       => '退勤時間',
            'breaks.*.start'     => '休憩開始',
            'breaks.*.end'       => '休憩終了',
            'remarks'            => '備考',
        ];
    }

    protected function prepareForValidation(): void
    {
        $norm = fn($v) => $v === '' ? null : $v;

        $this->merge([
            'work-start'   => $norm($this->input('work-start')),
            'work-end'     => $norm($this->input('work-end')),
            'clock_in_at'  => $norm($this->input('clock_in_at')),
            'clock_out_at' => $norm($this->input('clock_out_at')),
        ]);

        $breaks = $this->input('breaks', []);
        foreach ($breaks as $i => $row) {
            $row['start'] = $norm($row['start'] ?? null);
            $row['end']   = $norm($row['end']   ?? null);
            $breaks[$i]   = $row;
        }
        $this->merge(['breaks' => $breaks]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $attendanceId = $this->route('id');
            $attendance   = Attendance::find($attendanceId);

            if (!$attendance) {
                $v->errors()->add('form', '勤怠が見つかりませんでした。');
                return;
            }

            $base = $attendance->work_date
                ? Carbon::parse($attendance->work_date)->startOfDay()
                : Carbon::now()->startOfDay();

            $toCarbon = function (?string $hhmm) use ($base) {
                if (!$base || !$hhmm) return null;
                try {
                    [$h, $m] = array_map('intval', explode(':', $hhmm));
                    return $base->copy()->setTime($h, $m, 0);
                } catch (\Throwable $e) {
                    return null;
                }
            };

            $inStr  = $this->input('clock_in_at')  ?? $this->input('work-start');
            $outStr = $this->input('clock_out_at') ?? $this->input('work-end');

            $cin  = $toCarbon($inStr);
            $cout = $toCarbon($outStr);


            if ($cin && $cout && $cin->gte($cout)) {
                $v->errors()->add('work-start', '出勤時間もしくは退勤時間が不適切な値です');
                $v->errors()->add('work-end',   '出勤時間もしくは退勤時間が不適切な値です');
            }

            $rows = collect($this->input('breaks', []))->values();

            $normalized = $rows->map(function ($r) use ($toCarbon) {
                return [
                    'start_raw' => $r['start'] ?? null,
                    'end_raw'   => $r['end'] ?? null,
                    'start'     => $toCarbon($r['start'] ?? null),
                    'end'       => $toCarbon($r['end'] ?? null),
                ];
            })->filter(fn($r) => $r['start'] && $r['end'])->values();

            foreach ($normalized as $idx => $r) {
                if ($cin && $r['start'] && $r['start']->lt($cin)) {
                    $v->errors()->add("breaks.$idx.start", '休憩時間が不適切な値です');
                }
                if ($cout && $r['start'] && $r['start']->gt($cout)) {
                    $v->errors()->add("breaks.$idx.start", '休憩時間が不適切な値です');
                }
                if ($cout && $r['end'] && $r['end']->gt($cout)) {
                    $v->errors()->add("breaks.$idx.end", '休憩時間もしくは退勤時間が不適切な値です');
                }
                if ($r['start'] && $r['end'] && $r['start']->gte($r['end'])) {

                    $v->errors()->add("breaks.$idx.start", '休憩時間が不適切な値です');
                    $v->errors()->add("breaks.$idx.end",   '休憩時間が不適切な値です');
                }
            }

            $sorted = $normalized->sortBy('start')->values();
            for ($i = 1; $i < $sorted->count(); $i++) {
                $prev = $sorted[$i - 1];
                $curr = $sorted[$i];
                if ($prev['end']->gt($curr['start'])) {
                    $v->errors()->add("breaks." . ($i - 1) . ".end", '休憩時間が不適切な値です');
                    $v->errors()->add("breaks.$i.start",             '休憩時間が不適切な値です');
                }
            }
        });
    }
}
