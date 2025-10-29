<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\ApproveCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceApproval;
use App\Models\AttendanceApprovalBreak;
use App\Models\WorkBreak;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApprovalsController extends Controller
{
    public function index()
    {
        $rows = AttendanceApproval::with(['attendance.user'])
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.approvals.index', compact('rows'));
    }

    public function history()
    {
        $rows = AttendanceApproval::with(['attendance.user'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.approvals.history', compact('rows'));
    }

    public function show(AttendanceApproval $attendance_correct_request)
    {
        $attendance_correct_request->load([
            'attendance.user',
            'breaks' => fn($q) => $q->orderBy('sequence_no'),
        ]);

        $attendance = $attendance_correct_request->attendance;
        $user       = $attendance->user;

        $breaks = $attendance->breaks()->orderBy('break_started_at')->get();

        $hasPending = AttendanceApproval::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists();

        return view('admin.approvals.show', compact(
            'attendance_correct_request',
            'attendance',
            'user',
            'breaks',
            'hasPending'
        ));
    }

    public function approve(ApproveCorrectionRequest $request, AttendanceApproval $attendance_correct_request)
    {
        $decision = $request->validated()['decision'];

        DB::transaction(function () use ($decision, $attendance_correct_request, $request) {
            if ($decision === 'reject') {
                $attendance_correct_request->status         = 'rejected';
                $attendance_correct_request->admin_remarks  = (string)$request->input('admin_remarks');
                $attendance_correct_request->save();
                return;
            }

            $att = Attendance::with('breaks')->lockForUpdate()->findOrFail($attendance_correct_request->attendance_id);

            $att->clock_in_at  = $attendance_correct_request->proposed_clock_in_at ?: $att->clock_in_at;
            $att->clock_out_at = $attendance_correct_request->proposed_clock_out_at ?: $att->clock_out_at;
            if ($attendance_correct_request->proposed_remarks !== null) {
                $att->remarks = $attendance_correct_request->proposed_remarks;
            }

            $att->breaks()->delete();

            $base = $att->work_date instanceof Carbon
                ? $att->work_date->copy()->startOfDay()
                : Carbon::now()->startOfDay();

            foreach ($attendance_correct_request->breaks()->orderBy('sequence_no')->get() as $r) {
                $wb = new WorkBreak();
                $wb->attendance_id    = $att->id;
                $wb->break_started_at = $r->proposed_break_started_at ?: null;
                $wb->break_ended_at   = $r->proposed_break_ended_at ?: null;
                $wb->save();
            }

            if ($att->clock_in_at && !$att->clock_out_at) {
                $att->status = 'working';
            } elseif ($att->clock_in_at && $att->clock_out_at) {
                $att->status = 'done';
            } else {
                $att->status = 'not_working';
            }
            $att->save();

            $attendance_correct_request->status        = 'approved';
            $attendance_correct_request->admin_remarks = (string)$request->input('admin_remarks');
            $attendance_correct_request->save();
        });

        return back()->with('status', '申請を処理しました');
    }
}
