<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceApproval;
use App\Http\Requests\Admin\UpdateAttendanceRequest;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', '');
        $pattern = '/^(\d{4})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/';
        if (preg_match($pattern, $date, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
            $target = Carbon::createFromFormat('Y-m-d', $date);
        } else {
            $target = Carbon::today();
        }

        $dailyRows  = [];
        $current_day = $target->format('Y/m/d');
        $prev_date   = $target->copy()->subDays(1)->format('Y-m-d');
        $next_date   = $target->copy()->addDay()->format('Y-m-d');

        $attendances = Attendance::with(['breaks', 'user'])
            ->whereDate('work_date', $target)
            ->get();

        foreach ($attendances as $attendance) {
            $clock_in  = $attendance->clock_in_at;
            $clock_out = $attendance->clock_out_at;

            $break_seconds_total = 0;
            foreach ($attendance->breaks as $br) {
                if ($br->break_started_at && $br->break_ended_at) {
                    $break_seconds_total += $br->break_ended_at->diffInSeconds($br->break_started_at);
                }
            }

            $break_minutes_rounded = round($break_seconds_total / 60);
            $work_minutes_rounded  = 0;
            if ($clock_in && $clock_out) {
                $gross_second = $clock_out->diffInSeconds($clock_in);
                $work_second  = max(0, $gross_second - $break_seconds_total);
                $work_minutes_rounded = round($work_second / 60);
            }

            $break_hours   = floor($break_minutes_rounded / 60);
            $break_minutes = $break_minutes_rounded % 60;
            $work_hours    = floor($work_minutes_rounded / 60);
            $work_minutes  = $work_minutes_rounded % 60;

            $dailyRows[] = [
                'id'          => $attendance->id,
                'user'        => $attendance->user->name,
                'date'        => $attendance->work_date->isoFormat('MM/DD(ddd)'),
                'clock_in'    => $clock_in ? $clock_in->format('H:i') : '',
                'clock_out'   => $clock_out ? $clock_out->format('H:i') : '',
                'break_total' => sprintf('%d:%02d', $break_hours, $break_minutes),
                'work_total'  => sprintf('%d:%02d', $work_hours, $work_minutes),
            ];
        }

        return view('admin.attendances.index', compact('dailyRows', 'current_day', 'prev_date', 'next_date', 'target'));
    }

    public function show($id)
    {
        $attendance = Attendance::with([
            'user',
            'breaks' => fn($q) => $q->orderBy('break_started_at')->orderBy('id'),
        ])->findOrFail($id);

        $pending = AttendanceApproval::with([
            'breaks' => fn($q) => $q->orderBy('sequence_no')->orderBy('id'),
        ])->where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        [$resolvedClockIn, $resolvedClockOut, $resolvedBreaks, $resolvedRemarks] =
            $this->resolveForView($attendance, $pending);

        $has_pending = (bool) $pending;

        return view('admin.attendances.show', [
            'attendance'        => $attendance,
            'user'              => $attendance->user,
            'breaks'            => $attendance->breaks,
            'has_pending'       => $has_pending,
            'resolved_clock_in' => $resolvedClockIn,
            'resolved_clock_out' => $resolvedClockOut,
            'resolved_breaks'   => $resolvedBreaks,
            'resolved_remarks'  => $resolvedRemarks,
        ]);
    }

    public function update(UpdateAttendanceRequest $request, $id)
    {
        $attendance = Attendance::with('breaks')->findOrFail($id);

        $existsPending = AttendanceApproval::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        if ($existsPending) {
            return back()->withErrors([
                'pending' => '承認待ちの修正申請があります。先に承認または却下をしてください。',
            ])->withInput();
        }

        $base = $attendance->work_date ? Carbon::parse($attendance->work_date)->startOfDay() : null;
        $toCarbon = function (?string $hhmm) use ($base) {
            if (!$base || !$hhmm) return null;
            [$h, $m] = array_map('intval', explode(':', $hhmm));
            return $base->copy()->setTime($h, $m, 0);
        };

        DB::transaction(function () use ($request, $attendance, $toCarbon) {
            $inHm  = $request->input('clock_in_at')  ?? $request->input('work-start');
            $outHm = $request->input('clock_out_at') ?? $request->input('work-end');

            if ($inHm !== null && $inHm !== '') {
                $attendance->clock_in_at = $toCarbon($inHm);
            }
            if ($outHm !== null && $outHm !== '') {
                $attendance->clock_out_at = $toCarbon($outHm);
            }

            $attendance->remarks = $request->input('remarks');
            $attendance->breaks()->delete();

            $rows = collect($request->input('breaks', []))
                ->filter(fn($r) => ($r['start'] ?? null) || ($r['end'] ?? null))
                ->values();

            $seq = 1;
            foreach ($rows as $r) {
                $attendance->breaks()->create([
                    'sequence_no'      => $seq++,
                    'break_started_at' => $toCarbon($r['start'] ?? null),
                    'break_ended_at'   => $toCarbon($r['end']   ?? null),
                ]);
            }

            $attendance->save();
        });

        return back()->with('status', '勤怠を更新しました（管理）');
    }


    private function resolveForView(Attendance $attendance, ?AttendanceApproval $pending): array
    {
        $resolvedClockIn  = $pending?->proposed_clock_in_at  ?? $attendance->clock_in_at;
        $resolvedClockOut = $pending?->proposed_clock_out_at ?? $attendance->clock_out_at;

        $resolvedRemarks  = null;
        if ($pending) {
            $resolvedRemarks = $pending->proposed_remarks
                ?? ($pending->proposed_remarks ?? null);
        }

        if ($pending) {
            $resolvedBreaks = $pending->breaks->map(fn($b) => [
                'start' => $b->proposed_break_started_at,
                'end'   => $b->proposed_break_ended_at,
            ])->values();
        } else {
            $resolvedBreaks = $attendance->breaks->map(fn($b) => [
                'start' => $b->break_started_at,
                'end'   => $b->break_ended_at,
            ])->values();
        }

        return [$resolvedClockIn, $resolvedClockOut, $resolvedBreaks, $resolvedRemarks];
    }
}
