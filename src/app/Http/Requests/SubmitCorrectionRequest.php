<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attendance;
use App\Models\AttendanceApproval;

class SubmitCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!auth()->check()) {
            return false;
        }
        $attendance = $this->route('attendance');
        if (!$attendance instanceof Attendance) {
            return false;
        }
        if ($attendance->user_id !== auth()->id()) {
            return false;
        }

        $hasPending = AttendanceApproval::where('attendance_id', $attendance->id)->where('status', 'pending')
            ->exists();
        if ($hasPending) {
            return false;
        }

        return true;
    }

    public function rules()
    {
        return [
            'proposed_remarks' => ['required', 'string'],
            'work-start' => ['nullable', 'date_format:H:i'],
            'work-end'   => ['nullable', 'date_format:H:i'],
            'breaks'                 => ['nullable', 'array'],
            'breaks.*.start'         => ['nullable', 'date_format:H:i'],
            'breaks.*.end'           => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages()
    {
        return [
            'proposed_remarks.required' => '備考を記入してください',
            'work-start.date_format'    => '出勤時間もしくは退勤時間が不適切な値です',
            'work-end.date_format'      => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.start.date_format' => '休憩時間が不適切な値です',
            'breaks.*.end.date_format'  => '休憩時間が不適切な値です',
        ];
    }




    public function withValidator($validator): void
    {
        $validator->after(function ($v) {

            $attendance = $this->route('attendance');

            $current_ws = $attendance?->clock_in_at?->format('H:i');
            $current_we = $attendance?->clock_out_at?->format('H:i');

            $proposed_ws = $this->input('work-start');
            $proposed_we = $this->input('work-end');

            $ws_resolved = $proposed_ws ?? $current_ws;
            $we_resolved = $proposed_we ?? $current_we;

            if ($ws_resolved && $we_resolved && $this->toMinutes($ws_resolved) > $this->toMinutes($we_resolved)) {
                $v->errors()->add('work-start', '出勤時間が不適切な値です');
            }

            $breaks = $this->input('breaks', []);
            foreach ($breaks as $i => $b) {
                $bs = $b['start'] ?? null;
                $be = $b['end'] ?? null;

                if ($bs && $be && $this->toMinutes($bs) > $this->toMinutes($be)) {
                    $v->errors()->add("breaks.$i.end", '休憩時間が不適切な値です');
                }

                if ($bs && $we_resolved && $this->toMinutes($bs) > $this->toMinutes($we_resolved)) {
                    $v->errors()->add("breaks.$i.start", '休憩時間もしくは退勤時間が不適切な値です');
                }

                if ($be && $we_resolved && $this->toMinutes($be) > $this->toMinutes($we_resolved)) {
                    $v->errors()->add("breaks.$i.end", '休憩時間もしくは退勤時間が不適切な値です');
                }

                if ($bs && $ws_resolved && $this->toMinutes($bs) < $this->toMinutes($ws_resolved)) {
                    $v->errors()->add("breaks.$i.start", '休憩時間が不適切な値です');
                }
            }
            if (!$proposed_ws && !$proposed_we && !$this->hasAnyBreakInput($breaks)) {
                $v->errors()->add('proposed_remarks', '修正内容がありません。いずれかの項目を入力してください');
            }
        });
    }

    private function toMinutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $h * 60 + $m;
    }

    private function hasAnyBreakInput($breaks)
    {
        foreach ($breaks as $b) {
            if (!empty($b['start']) || !empty($b['end'])) {
                return true;
            }
        }
        return false;
    }
}
