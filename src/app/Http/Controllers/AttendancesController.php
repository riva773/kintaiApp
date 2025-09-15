<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\WorkBreak;
use Illuminate\Support\Facades\DB;

class AttendancesController extends Controller
{
    public function create()
    {
        $now = Carbon::now();
        $now_date = $now->format(('Y-m-d'));
        $time = $now->format('H:i');
        $status = Attendance::where('user_id', auth()->id())->where('work_date', $now_date)->value('status');
        if ($status === null) {
            $status = 'not_working';
        }
        return view('create', compact('status', 'now', 'time'));
    }

    public function store(Request $request)
    {
        $now = Carbon::now();
        $now_date = $now->format('Y-m-d');
        $action = $request->input('action');
        $attendance = Attendance::where('user_id', auth()->id())->where('work_date', $now_date)->first();

        if ($action == 'clock_in') {
            if (is_null($attendance)) {
                Attendance::create([
                    'user_id'     => auth()->id(),
                    'work_date'   => $now_date,
                    'status'      => 'working',
                    'clock_in_at' => $now,
                ]);
                return redirect('/attendance')->with('success', '出勤しました。');
            }
            if (is_null($attendance->clock_in_at)) {
                $attendance->status = 'working';
                $attendance->clock_in_at = $now;
                $attendance->clock_out_at = null;
                $attendance->save();
                return redirect('/attendance')->with('success', '出勤しました。');
            }
            return redirect('/attendance')->with('error', '既に出勤済みです。');
        } elseif ($action == 'clock_out') {
            if (is_null($attendance)) {
                return redirect('/attendance')->with('error', '出勤していません。');
            }
            if ($attendance->clock_out_at) {
                return redirect('/attendance')->with('error', '既に退勤済みです。');
            }
            $hasOpenBreak = $attendance->breaks()
                ->whereNull('break_ended_at')
                ->exists();
            if ($hasOpenBreak) {
                return redirect('/attendance')->with('error', '休憩を終了してから退勤してください。');
            }

            $attendance->status = 'finished';
            $attendance->clock_out_at = $now;
            $attendance->save();
            return redirect('/attendance');
        } elseif ($action == "break_start") {
            if (is_null($attendance) || is_null($attendance->clock_in_at)) {
                return redirect('/attendance')->with('error', '出勤していません。');
            }
            if (!is_null($attendance->clock_out_at)) {
                return redirect('/attendance')->with('error', '既に退勤済みです。');
            }
            try {
                DB::transaction(function () use ($now, $attendance) {
                    $att = Attendance::where('id', $attendance->id)->lockForUpdate()->first();

                    $hasOpenBreak = $att->breaks()->whereNull('break_ended_at')->lockForUpdate()->exists();
                    if ($hasOpenBreak) {
                        throw new \RuntimeException(('既に休憩中です。'));
                    }
                    $att->breaks()->create([
                        'user_id'          => auth()->id(),
                        'break_started_at' => $now,
                    ]);
                    $att->status = 'on_break';
                    $att->save();
                });
            } catch (\RuntimeException $e) {
                return redirect('/attendance')->with('error', $e->getMessage());
            } catch (\Throwable $e) {
                return redirect('/attendance')->with('error', '処理中にエラーが発生しました。もう一度お試しください。');
            }
            return redirect('/attendance')->with('success', '休憩に入りました。');
        } elseif ($action == "break_end") {
            if (is_null($attendance) || is_null($attendance->clock_in_at)) {
                return redirect('/attendance')->with('error', '出勤していません。');
            }
            if (!is_null($attendance->clock_out_at)) {
                return redirect('/attendance')->with('error', '既に退勤済みです。');
            }

            try {
                DB::transaction(function () use ($attendance, $now) {
                    $att = Attendance::whereKey($attendance->id)
                        ->lockForUpdate()
                        ->first();
                    $openBreak = $att->breaks()
                        ->whereNull('break_ended_at')
                        ->orderByDesc('break_started_at')
                        ->lockForUpdate()
                        ->first();

                    if (!$openBreak) {
                        throw new \RuntimeException('開始中の休憩はありません。');
                    }

                    $openBreak->break_ended_at = $now;
                    $openBreak->save();

                    $att->status = 'working';
                    $att->save();
                });
            } catch (\RuntimeException $e) {
                return redirect('/attendance')->with('error', $e->getMessage());
            } catch (\Throwable $e) {
                return redirect('/attendance')->with('error', '処理中にエラーが発生しました。もう一度お試しください。');
            }
            return redirect('/attendance')->with('success', '休憩を終了しました。');
        }
    }
}
