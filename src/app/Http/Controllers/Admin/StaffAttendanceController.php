<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffMonthQueryRequest;
use App\Http\Requests\Admin\StaffAttendanceCsvRequest;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    public function index(StaffMonthQueryRequest $request, $id)
    {
        $validated   = $request->validated();
        $ym          = $validated['ym'];
        $targetMonth = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();

        $user  = User::findOrFail($id);
        $start = $targetMonth->copy()->startOfMonth();
        $end   = $targetMonth->copy()->endOfMonth();

        $rows = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->orderBy('id')
            ->get();

        $current_year_month = $targetMonth->format('Y/m');
        $prevYm = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextYm = $targetMonth->copy()->addMonth()->format('Y-m');

        return view('admin.staff_attendances.index', compact(
            'user',
            'rows',
            'current_year_month',
            'prevYm',
            'nextYm'
        ));
    }

    public function csv(StaffAttendanceCsvRequest $request)
    {
        $v  = $request->validated();
        $id = (int)$v['user_id'];
        $ym = $v['ym'];

        $targetMonth = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
        $user  = User::findOrFail($id);
        $start = $targetMonth->copy()->startOfMonth()->toDateString();
        $end   = $targetMonth->copy()->endOfMonth()->toDateString();

        $rows = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date')
            ->orderBy('id')
            ->get();

        $headers = [
            '日付',
            '出勤',
            '退勤',
            '休憩（複数は;区切り）',
            '備考'
        ];

        $lines = [];
        $lines[] = implode(',', $headers);

        foreach ($rows as $row) {
            $breaks = $row->breaks()->orderBy('break_started_at')->get()
                ->map(function ($b) {
                    $s = $b->break_started_at ? Carbon::parse($b->break_started_at)->format('H:i') : '';
                    $e = $b->break_ended_at ? Carbon::parse($b->break_ended_at)->format('H:i') : '';
                    return trim($s . '-' . $e, '-');
                })->filter()->implode(';');

            $lines[] = implode(',', [
                $row->work_date ? Carbon::parse($row->work_date)->format('Y-m-d') : '',
                $row->clock_in_at ? Carbon::parse($row->clock_in_at)->format('H:i') : '',
                $row->clock_out_at ? Carbon::parse($row->clock_out_at)->format('H:i') : '',
                $breaks,
                str_replace(["\r", "\n", ","], [' ', ' ', ' '], (string)$row->remarks),
            ]);
        }

        $csv = implode("\n", $lines);
        $filename = sprintf('attendance_%s_%s.csv', $user->id, $targetMonth->format('Ym'));

        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
