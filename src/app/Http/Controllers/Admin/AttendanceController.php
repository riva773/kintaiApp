<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\WorkBreak;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $ym = request()->query('ym');
        if ($ym && preg_match('/^\d{4}-\d{2}$/', $ym)) {
            try {
                $target = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
            } catch (\Throwable $e) {
                $target = Carbon::now()->startOfMonth();
            }
        } else {
            $target = Carbon::now()->startOfMonth();
        }

        $prev_ym             = $target->copy()->subMonth()->format('Y-m');
        $next_ym             = $target->copy()->addMonth()->format('Y-m');
        $current_year_month  = $target->format('Y/m');

        $rows = Attendance::with('user')
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.attendances.index', compact(
            'rows',
            'target',
            'prev_ym',
            'next_ym',
            'current_year_month'
        ));
    }

    public function update(UpdateAttendanceRequest $request, $id)
    {
        $att = Attendance::with(['breaks' => function ($q) {
            $q->orderBy('break_started_at')->orderBy('id');
        }])->findOrFail($id);

        $base = $att->work_date instanceof Carbon
            ? $att->work_date->copy()->startOfDay()
            : Carbon::now()->startOfDay();

        $toC = function (?string $hhmm) use ($base) {
            if (!$base || !$hhmm) return null;
            [$h, $m] = array_map('intval', explode(':', $hhmm));
            return $base->copy()->setTime($h, $m, 0);
        };

        $in   = $toC($request->input('clock_in_at')  ?? $request->input('work-start'));
        $out  = $toC($request->input('clock_out_at') ?? $request->input('work-end'));
        $rows = collect($request->input('breaks', []))
            ->filter(fn($r) => ($r['start'] ?? null) && ($r['end'] ?? null))
            ->values();

        DB::transaction(function () use ($att, $in, $out, $rows, $toC, $request) {
            $att->clock_in_at  = $in;
            $att->clock_out_at = $out;
            $att->remarks      = (string)$request->input('remarks');
            if ($att->clock_in_at && !$att->clock_out_at) {
                $att->status = 'working';
            } elseif ($att->clock_in_at && $att->clock_out_at) {
                $att->status = 'done';
            } else {
                $att->status = 'not_working';
            }
            $att->save();

            $att->breaks()->delete();

            foreach ($rows as $r) {
                $wb = new WorkBreak();
                $wb->attendance_id     = $att->id;
                $wb->break_started_at  = $toC($r['start'] ?? null);
                $wb->break_ended_at    = $toC($r['end']   ?? null);
                $wb->save();
            }
        });

        return redirect()->route('admin.attendance.show', ['id' => $att->id])
            ->with('status', '勤怠を更新しました');
    }
}
