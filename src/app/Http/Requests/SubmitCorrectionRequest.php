<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class SubmitCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'work-start'         => ['nullable', 'date_format:H:i'],
            'work-end'           => ['nullable', 'date_format:H:i'],
            'breaks'             => ['nullable', 'array'],
            'breaks.*.start'     => ['nullable', 'date_format:H:i'],
            'breaks.*.end'       => ['nullable', 'date_format:H:i'],
            'proposed_remarks'   => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'work-start.date_format'        => '出勤時間もしくは退勤時間が不適切な値です',
            'work-end.date_format'          => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start.date_format'    => '休憩時間が不適切な値です',
            'breaks.*.end.date_format'      => '休憩時間が不適切な値です',
            'proposed_remarks.required'     => '備考を記入してください',
            'proposed_remarks.max'          => '備考を記入してください',
        ];
    }

    public function attributes(): array
    {
        return [
            'work-start'              => '出勤時間',
            'work-end'                => '退勤時間',
            'breaks.*.start'          => '休憩開始',
            'breaks.*.end'            => '休憩終了',
            'proposed_remarks'        => '備考',
        ];
    }

    protected function prepareForValidation(): void
    {
        $norm = fn($v) => $v === '' ? null : $v;

        $this->merge([
            'work-start'       => $norm($this->input('work-start')),
            'work-end'         => $norm($this->input('work-end')),
            'proposed_remarks' => $this->input('proposed_remarks'),
        ]);

        $breaks = $this->input('breaks', []);
        foreach ($breaks as $i => $row) {
            $row['start'] = $norm($row['start'] ?? null);
            $row['end']   = $norm($row['end']   ?? null);
            $breaks[$i]   = $row;
        }
        $this->merge(['breaks' => $breaks]);
    }

    public function withValidator($validator)
    {
        $validator->after(function (Validator $v) {
            $attendance = $this->route('attendance');
            $baseDate   = $attendance?->work_date instanceof Carbon
                ? $attendance->work_date->copy()->startOfDay()
                : Carbon::now()->startOfDay();

            $toC = function (?string $hhmm) use ($baseDate) {
                if (!$baseDate || !$hhmm) return null;
                try {
                    [$h, $m] = array_map('intval', explode(':', $hhmm));
                    return $baseDate->copy()->setTime($h, $m, 0);
                } catch (\Throwable $e) {
                    return null;
                }
            };

            $effIn  = $toC($this->input('work-start')) ?? $attendance?->clock_in_at;
            $effOut = $toC($this->input('work-end'))   ?? $attendance?->clock_out_at;

            if ($effIn && $effOut && $effIn->gte($effOut)) {
                $v->errors()->add('work-start', '出勤時間もしくは退勤時間が不適切な値です');
                $v->errors()->add('work-end',   '出勤時間もしくは退勤時間が不適切な値です');
            }

            $breaks = $this->input('breaks', []);
            foreach ($breaks as $i => $row) {
                $bs = $toC($row['start'] ?? null);
                $be = $toC($row['end']   ?? null);

                if (!$bs && !$be) continue;

                if ($bs && $be && $bs->gte($be)) {
                    $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                    $v->errors()->add("breaks.$i.end",   '休憩時間が不適切な値です');
                }

                if ($effIn && $bs && $bs->lt($effIn)) {
                    $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                }

                if ($effOut && $bs && $bs->gt($effOut)) {
                    $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                }

                if ($effOut && $be && $be->gt($effOut)) {
                    $v->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}
