<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceApproval;
use App\Models\AttendanceApprovalBreak;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
            $has_open_breakBreak = $attendance->breaks()
                ->whereNull('break_ended_at')
                ->exists();
            if ($has_open_breakBreak) {
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

                    $has_open_breakBreak = $att->breaks()->whereNull('break_ended_at')->lockForUpdate()->exists();
                    if ($has_open_breakBreak) {
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
                    $open_break = $att->breaks()
                        ->whereNull('break_ended_at')
                        ->orderByDesc('break_started_at')
                        ->lockForUpdate()
                        ->first();

                    if (!$open_break) {
                        throw new \RuntimeException('開始中の休憩はありません。');
                    }

                    $open_break->break_ended_at = $now;
                    $open_break->save();

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

    public function index(Request $request)
    {
        $ym = $request->query('ym', '');
        if ($ym && preg_match('/^\d{4}-\d{2}$/', $ym)) {
            try {
                $target_month = Carbon::createFromFormat('Y-m', $ym);
            } catch (\Exception $e) {
                $target_month = Carbon::now()->startOfMonth();
            }
        } else {
            $target_month = Carbon::now()->startOfMonth();
        }
        $daily_rows = [];
        $current_year_month = $target_month->format('Y/m');
        $start_date = $target_month->copy()->startOfMonth();
        $last_date = $target_month->copy()->lastOfMonth();
        $prev_ym = $target_month->copy()->subMonths(1)->format('Y-m');
        $next_ym = $target_month->copy()->addMonth()->format('Y-m');
        $user = Auth::user();
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start_date, $last_date])
            ->get();
        foreach ($attendances as $attendance) {
            $clock_in = $attendance->clock_in_at;
            $clock_out = $attendance->clock_out_at;
            $break_seconds_total = 0;
            foreach ($attendance->breaks as $br) {
                if ($br->break_started_at && $br->break_ended_at) {
                    $break_seconds_total += $br->break_ended_at->diffInSeconds($br->break_started_at);
                }
            }
            $break_minutes_rounded = round($break_seconds_total / 60);

            $work_minutes_rounded = 0;
            if ($clock_in && $clock_out) {
                $gross_second = $clock_out->diffInSeconds($clock_in);
                $work_second = max(0, $gross_second - $break_seconds_total);
                $work_minutes_rounded = round($work_second / 60);
            }
            $break_hours = floor($break_minutes_rounded / 60);
            $break_minutes = $break_minutes_rounded % 60;
            $work_hours = floor($work_minutes_rounded / 60);
            $work_minutes = $work_minutes_rounded % 60;

            $display = [
                'id' => $attendance->id,
                'date' => $attendance->work_date->isoFormat('MM/DD(ddd)'),
                'clock_in' => $clock_in ? $clock_in->format('H:i') : '',
                'clock_out' => $clock_out ? $clock_out->format('H:i') : '',
                'break_total' => sprintf('%d:%02d', $break_hours, $break_minutes),
                'work_total' => sprintf('%d:%02d', $work_hours, $work_minutes),
            ];
            $daily_rows[] = $display;
        }
        return view('index', compact('daily_rows', 'current_year_month', 'prev_ym', 'next_ym'));
    }
    public function show($id)
    {
        $user = Auth::user();
        $attendance = Attendance::with(['breaks' => function ($q) {
            $q->orderBy('break_started_at')->orderBy('id');
        }])->where('user_id', $user->id)->findOrFail($id);

        $pending_approval = AttendanceApproval::with([
            'breaks' => fn($q) => $q->orderBy('proposed_break_started_at')->orderBy('id')
        ])
            ->where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        $has_pending = (bool) $pending_approval;
        $breaks = $attendance->breaks;

        $resolved_clock_in  = null;
        $resolved_clock_out = null;
        $resolved_remarks   = null;
        $resolved_breaks    = collect();

        if ($has_pending) {
            $resolved_clock_in  = $pending_approval->proposed_clock_in_at  ?? $attendance->clock_in_at;
            $resolved_clock_out = $pending_approval->proposed_clock_out_at ?? $attendance->clock_out_at;
            $resolved_remarks   = !is_null($pending_approval->proposed_remarks)
                ? $pending_approval->proposed_remarks
                : ($attendance->remarks ?? null);

            if ($pending_approval->breaks->isNotEmpty()) {
                $resolved_breaks = $pending_approval->breaks->map(function ($br) {
                    return [
                        'start' => $br->proposed_break_started_at,
                        'end'   => $br->proposed_break_ended_at,
                    ];
                });
            } else {
                $resolved_breaks = $attendance->breaks->map(function ($br) {
                    return [
                        'start' => $br->break_started_at,
                        'end'   => $br->break_ended_at,
                    ];
                });
            }
        }

        return view('show', compact(
            'user',
            'attendance',
            'breaks',
            'has_pending',
            'resolved_clock_in',
            'resolved_clock_out',
            'resolved_remarks',
            'resolved_breaks'
        ));
    }

    public function submitCorrection(SubmitCorrectionRequest $request, Attendance $attendance)
    {
        $user = Auth::user();
        $current_clock_in = $attendance->clock_in_at?->format('H:i');
        $current_clock_out = $attendance->clock_out_at?->format('H:i');
        $proposed_ws = $request->input('work-start');
        $proposed_we = $request->input('work-end');
        $changed_clock_in = $proposed_ws !== null && $proposed_ws !== $current_clock_in;
        $changed_clock_out = $proposed_we !== null && $proposed_we !== $current_clock_out;

        $current_breaks = [];
        foreach ($attendance->breaks as $br) {
            $current_breaks[] = [
                'start' => $br->break_started_at?->format('H:i'),
                'end' => $br->break_ended_at?->format('H:i'),
            ];
        }
        $input_breaks = $request->input('breaks', []);
        $new_breaks = [];
        foreach ($input_breaks as $row) {
            $bs = $row['start'] ?? null;
            $be = $row['end'] ?? null;
            if ($bs === null && $be === null) {
                continue;
            }
            $new_breaks[] = [
                'start' => $bs,
                'end' => $be,
            ];
        }
        $changed_breaks = ($new_breaks !== $current_breaks);

        if (!$changed_clock_in && !$changed_clock_out && !$changed_breaks) {
            return back()->withInput()->withErrors(['proposed_remarks' => '修正内容がありません。いずれかの項目を変更してください。']);
        }

        DB::transaction(function () use ($request, $attendance, $user, $changed_clock_in, $changed_clock_out, $changed_breaks, $proposed_ws, $proposed_we, $new_breaks) {
            $ap = new AttendanceApproval();
            $ap->attendance()->associate($attendance);
            $ap->user()->associate($user);

            if ($changed_clock_in) {
                $ap->proposed_clock_in_at = $this->combineDateHhmm($attendance->work_date, $proposed_ws);
            }
            if ($changed_clock_out) {
                $ap->proposed_clock_out_at = $this->combineDateHhmm($attendance->work_date, $proposed_we);
            }
            $ap->proposed_remarks = $request->input('proposed_remarks');
            $ap->status = 'pending';
            $ap->save();
            if (!empty($new_breaks)) {
                foreach ($new_breaks as $i => $row) {
                    $bs = $row['start'];
                    $be = $row['end'];
                    $br = new AttendanceApprovalBreak();
                    $br->attendance_approval_id = $ap->id;
                    $br->sequence_no = $i + 1;
                    if ($bs !== null) {
                        $br->proposed_break_started_at = $this->combineDateHhmm($attendance->work_date, $bs);
                    }
                    if ($be !== null) {
                        $br->proposed_break_ended_at = $this->combineDateHhmm($attendance->work_date, $be);
                    }
                    $br->save();
                }
            }
        });

        return redirect()->route('attendance.show', ['id' => $attendance->id])->with('status', '修正申請を受け付けました。');
    }
    private function combineDateHhmm(?Carbon $date, ?String $hhmm)
    {
        if ($date === null || $hhmm === null || $hhmm === '') {
            return null;
        }
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        return $date->copy()->setTime($h, $m, 0);
    }
}
