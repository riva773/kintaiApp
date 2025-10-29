<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffAttendanceCsvController extends Controller
{
    public function __invoke(Request $request, int $id)
    {
        $ym = $request->query('ym');
        try {
            $target = $ym ? Carbon::createFromFormat('Y-m', $ym) : now('Asia/Tokyo');
        } catch (\Throwable $e) {
            $target = now('Asia/Tokyo');
        }
        $start = $target->copy()->startOfMonth();
        $end   = $target->copy()->endOfMonth();

        $attendances = Attendance::with([
            'breaks' => function ($q) {
                $q->whereNotNull('break_started_at')
                    ->whereNotNull('break_ended_at')
                    ->orderBy('break_started_at');
            }
        ])
            ->where('user_id', $id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        $rows = [];
        $rows[] = ['日付', '出勤', '退勤', '休憩合計', '実働時間'];

        foreach ($attendances as $a) {
            $in  = optional($a->clock_in_at)?->copy()->second(0);
            $out = optional($a->clock_out_at)?->copy()->second(0);

            $grossMin = ($in && $out) ? $in->diffInMinutes($out) : 0;

            $restMin = $a->breaks->sum(function ($br) {
                $s = optional($br->break_started_at)?->copy()->second(0);
                $e = optional($br->break_ended_at)?->copy()->second(0);
                if (!$s || !$e || $e->lessThanOrEqualTo($s)) {
                    return 0;
                }
                return $s->diffInMinutes($e);
            });

            $netMin = max(0, $grossMin - $restMin);

            $rows[] = [
                optional($a->work_date)?->format('Y-m-d'),
                $in?->format('H:i') ?? '',
                $out?->format('H:i') ?? '',
                self::fmtMin($restMin),
                self::fmtMin($netMin),
            ];
        }

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                $encoded = array_map(fn($v) => mb_convert_encoding($v, 'SJIS-win', 'UTF-8'), $row);
                fputcsv($out, $encoded);
            }
            fclose($out);
        };

        $filename = sprintf('attendance_user_%d_%s.csv', $id, $target->format('Y-m'));
        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=Shift_JIS',
        ]);
    }

    private static function fmtMin(int $m): string
    {
        $h  = intdiv($m, 60);
        $mm = $m % 60;
        return sprintf('%d:%02d', $h, $mm);
    }
}
