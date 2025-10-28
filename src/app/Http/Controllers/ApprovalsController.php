<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AttendanceApproval;

class ApprovalsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
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

    public function show(AttendanceApproval $attendance_correct_request)
    {
        $attendance_correct_request->load([
            'attendance.user',
            'attendance.breaks' => fn($q) => $q->orderBy('break_started_at')->orderBy('id'),
            'breaks' => fn($q) => $q->orderBy('proposed_break_started_at')->orderBy('id'),
        ]);

        $approval   = $attendance_correct_request;
        $attendance = $approval->attendance;
        $user       = $attendance->user;

        $resolved_clock_in  = $approval->proposed_clock_in_at  ?? $attendance->clock_in_at;
        $resolved_clock_out = $approval->proposed_clock_out_at ?? $attendance->clock_out_at;
        $resolved_remarks   = !is_null($approval->proposed_remarks)
            ? $approval->proposed_remarks
            : ($attendance->remarks ?? null);

        if ($approval->breaks->isNotEmpty()) {
            $resolved_breaks = $approval->breaks->map(function ($br) {
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

    public function approve(Request $request, AttendanceApproval $attendance_correct_request)
    {
        if ($attendance_correct_request->status !== 'pending') {
            return redirect()
                ->route('approvals.show', ['attendance_correct_request' => $attendance_correct_request])
                ->with('status', 'この申請は既に処理済みです。');
        }

        $attendance_correct_request->load([
            'attendance',
            'breaks' => fn($q) => $q->orderBy('proposed_break_started_at')->orderBy('id')
        ]);

        DB::transaction(function () use ($attendance_correct_request) {
            $attendance = $attendance_correct_request->attendance()->lockForUpdate()->first();

            if (!is_null($attendance_correct_request->proposed_clock_in_at)) {
                $attendance->clock_in_at = $attendance_correct_request->proposed_clock_in_at;
            }
            if (!is_null($attendance_correct_request->proposed_clock_out_at)) {
                $attendance->clock_out_at = $attendance_correct_request->proposed_clock_out_at;
            }
            if (!is_null($attendance_correct_request->proposed_remarks)) {
                $attendance->remarks = $attendance_correct_request->proposed_remarks;
            }
            $attendance->save();

            if ($attendance_correct_request->breaks->isNotEmpty()) {
                $attendance->breaks()->delete();
                foreach ($attendance_correct_request->breaks as $ap_break) {
                    $start = $ap_break->proposed_break_started_at;
                    $end   = $ap_break->proposed_break_ended_at;
                    if (is_null($start)) {
                        continue; 
                    }
                    $attendance->breaks()->create([
                        'break_started_at' => $start,
                        'break_ended_at'   => $end,
                    ]);
                }
            }

            $attendance_correct_request->status = 'approved';
            $attendance_correct_request->save();
        });

        return redirect()
            ->route('approvals.show', ['attendance_correct_request' => $attendance_correct_request])
            ->with('status', '申請を承認し、勤怠に反映しました。');
    }
}
