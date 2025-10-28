<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Attendance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;


class StaffAttendanceController extends Controller
{
    public function index(Request $request, $id)
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
        $dailyRows = [];
        $current_year_month = $target_month->format('Y/m');
        $start_date = $target_month->copy()->startOfMonth();
        $last_date = $target_month->copy()->lastOfMonth();
        $prevYm = $target_month->copy()->subMonths(1)->format('Y-m');
        $nextYm = $target_month->copy()->addMonth()->format('Y-m');
        $user = User::findOrFail($id);
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
            $dailyRows[] = $display;
        }
        return view('admin.staff-attendances.index', compact('dailyRows', 'user', 'current_year_month', 'prevYm', 'nextYm'));
    }
}
