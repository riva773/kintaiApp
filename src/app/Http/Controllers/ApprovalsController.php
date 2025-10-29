<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\AttendanceApproval;

class ApprovalsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user && $user->role === 'admin') {
            return $this->indexForAdmin($request);
        } else {
            return $this->indexForGeneral($request);
        }
    }

    public function indexForAdmin(Request $request)
    {
        $status = $request->query('status');
        if (!in_array($status, ['pending', 'approved'], true)) {
            $status = 'pending';
        }

        $approvals = AttendanceApproval::with(['user', 'attendance'])
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.approvals.index', compact('approvals', 'status'));
    }

    public function indexForGeneral(Request $request)
    {
        $user = auth()->user();
        $status = $request->query('status');
        if (!in_array($status, ['pending', 'approved'], true)) {
            $status = 'pending';
        }

        $approvals = AttendanceApproval::with('attendance')
            ->where('user_id', $user->id)
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->get();

        return view('approvals.index', compact('approvals', 'user', 'status'));
    }

    public function show($id)
    {
        $approval = AttendanceApproval::with([
            'attendance.user',
            'attendance.breaks' => fn($q) => $q->orderBy('break_started_at')->orderBy('id'),
            'breaks'            => fn($q) => $q->orderBy('proposed_break_started_at')->orderBy('id'),
        ])->findOrFail($id);

        $attendance = $approval->attendance;
        $user       = $attendance->user;

        $resolved_clock_in  = $approval->proposed_clock_in_at  ?? $attendance->clock_in_at;
        $resolved_clock_out = $approval->proposed_clock_out_at ?? $attendance->clock_out_at;
        $resolved_remarks   = !is_null($approval->proposed_remarks)
            ? $approval->proposed_remarks
            : ($attendance->remarks ?? null);

        if ($approval->breaks->isNotEmpty()) {
            $resolved_breaks = $approval->breaks->map(fn($br) => [
                'start' => $br->proposed_break_started_at,
                'end'   => $br->proposed_break_ended_at,
            ]);
        } else {
            $resolved_breaks = $attendance->breaks->map(fn($br) => [
                'start' => $br->break_started_at,
                'end'   => $br->break_ended_at,
            ]);
        }

        $is_pending = ($approval->status === 'pending');

        return view('admin.approvals.show', compact(
            'user',
            'attendance',
            'approval',
            'resolved_clock_in',
            'resolved_clock_out',
            'resolved_remarks',
            'resolved_breaks',
            'is_pending'
        ));
    }

    public function approve(Request $request, $id)
    {
        $approval = AttendanceApproval::with([
            'attendance',
            'breaks' => fn($q) => $q->orderBy('proposed_break_started_at')->orderBy('id')
        ])->findOrFail($id);

        if ($approval->status !== 'pending') {
            return redirect()
                ->route('admin.approvals.show', ['id' => $approval->id])
                ->with('status', 'この申請は既に処理済みです。');
        }

        DB::transaction(function () use ($approval) {
            $attendance = $approval->attendance()->lockForUpdate()->firstOrFail();

            if (!is_null($approval->proposed_clock_in_at)) {
                $attendance->clock_in_at = $approval->proposed_clock_in_at;
            }
            if (!is_null($approval->proposed_clock_out_at)) {
                $attendance->clock_out_at = $approval->proposed_clock_out_at;
            }
            if (!is_null($approval->proposed_remarks)) {
                $attendance->remarks = $approval->proposed_remarks;
            }
            $attendance->save();

            if ($approval->breaks->isNotEmpty()) {
                $attendance->breaks()->delete();

                $seq = 1;
                foreach ($approval->breaks as $ap_break) {
                    $start = $ap_break->proposed_break_started_at;
                    $end   = $ap_break->proposed_break_ended_at;

                    if (is_null($start)) {
                        continue;
                    }

                    $attendance->breaks()->create([
                        'sequence_no'      => $seq++,
                        'break_started_at' => $start,
                        'break_ended_at'   => $end,
                    ]);
                }
            }

            $approval->status = 'approved';
            $approval->save();
        });

        return redirect()
            ->route('admin.approvals.show', ['id' => $approval->id])
            ->with('status', '申請を承認し、勤怠に反映しました。');
    }
}
