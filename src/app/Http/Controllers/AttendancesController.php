<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceStampRequest;
use App\Http\Requests\SubmitCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceApproval;
use App\Models\AttendanceApprovalBreak;
use App\Models\WorkBreak; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendancesController extends Controller
{
    public function create()
    {
        $now   = Carbon::now();
        $time  = $now->format('H:i');
        $today = $now->toDateString();

        $attendance = Attendance::with(['breaks' => function ($q) {
            $q->orderByDesc('break_started_at')->orderByDesc('id');
        }])
            ->where('user_id', auth()->id())
            ->whereDate('work_date', $today)
            ->latest('id')
            ->first();

        $hasOpenBreak = false;
        $uiStatus     = 'not_working';

        if ($attendance) {
            $hasOpenBreak = $attendance->breaks()->whereNull('break_ended_at')->exists();
            $uiStatus     = $attendance->status ?? 'not_working';
        }

        return view('create', compact('attendance', 'hasOpenBreak', 'uiStatus', 'now', 'time'));
    }

    public function store(AttendanceStampRequest $request)
    {
        $userId = auth()->id();
        $now    = Carbon::now();
        $today  = $now->toDateString();
        $action = $request->validated()['action'];

        DB::transaction(function () use ($userId, $today, $now, $action) {
            $att = Attendance::with(['breaks' => function ($q) {
                $q->orderByDesc('break_started_at')->orderByDesc('id');
            }])
                ->where('user_id', $userId)
                ->whereDate('work_date', $today)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($action === 'clock_in') {
                if (!$att) {
                    $att = new Attendance();
                    $att->user_id      = $userId;
                    $att->work_date    = $today;
                }
                $att->clock_in_at = $now;
                $att->status      = 'working';
                $att->save();
                return;
            }

            if (!$att) {
                abort(422, '本日の勤怠が見つかりません。');
            }

            if ($action === 'break_in') {
                $wb = new WorkBreak();
                $wb->attendance_id   = $att->id;
                $wb->break_started_at = $now;
                $wb->save();

                $att->status = 'on_break';
                $att->save();
                return;
            }

            if ($action === 'break_out') {
                $open = $att->breaks()->whereNull('break_ended_at')->orderByDesc('id')->lockForUpdate()->first();
                if (!$open) {
                    abort(422, '終了できる休憩が見つかりません。');
                }
                $open->break_ended_at = $now;
                $open->save();

                $att->status = 'working';
                $att->save();
                return;
            }

            if ($action === 'clock_out') {
                $att->clock_out_at = $now;
                $att->status       = 'done';
                $att->save();
                return;
            }
        });

        return back()->with('status', '打刻しました');
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breaks' => function ($q) {
            $q->orderBy('break_started_at')->orderBy('id');
        }])->findOrFail($id);

        $user = $attendance->user;

        return view('show', compact('attendance', 'user'));
    }

    public function submitCorrection(SubmitCorrectionRequest $request, Attendance $attendance)
    {
        $user = Auth::user();
        if ($attendance->user_id !== $user->id) {
            abort(403);
        }

        $base = $attendance->work_date instanceof Carbon
            ? $attendance->work_date->copy()->startOfDay()
            : Carbon::now()->startOfDay();

        $toC = function (?string $hhmm) use ($base) {
            if (!$base || !$hhmm) return null;
            [$h, $m] = array_map('intval', explode(':', $hhmm));
            return $base->copy()->setTime($h, $m, 0);
        };

        $in   = $toC($request->input('work-start'));
        $out  = $toC($request->input('work-end'));
        $rows = collect($request->input('breaks', []))
            ->filter(fn($r) => ($r['start'] ?? null) && ($r['end'] ?? null))
            ->values();

        DB::transaction(function () use ($attendance, $in, $out, $rows, $request, $toC) {
            $ar = new AttendanceApproval();
            $ar->attendance_id         = $attendance->id;
            $ar->proposed_clock_in_at  = $in;
            $ar->proposed_clock_out_at = $out;
            $ar->proposed_remarks      = (string)$request->input('proposed_remarks');
            $ar->status                = 'pending';
            $ar->save();

            foreach ($rows as $i => $r) {
                $br = new AttendanceApprovalBreak();
                $br->attendance_approval_id   = $ar->id;
                $br->sequence_no              = $i + 1;
                $br->proposed_break_started_at = $toC($r['start'] ?? null);
                $br->proposed_break_ended_at   = $toC($r['end'] ?? null);
                $br->save();
            }
        });

        return back()->with('status', '修正申請を送信しました');
    }
}
