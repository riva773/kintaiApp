<?php

namespace Database\Factories;

use App\Models\WorkBreak;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class WorkBreakFactory extends Factory
{
    protected $model = WorkBreak::class;

    public function definition(): array
    {
        $start = Carbon::now('Asia/Tokyo')->setTime(12, 0, 0);
        $end   = $start->copy()->addMinutes(30);

        return [
            'attendance_id'    => Attendance::factory(),
            'sequence_no'      => 1,
            'break_started_at' => $start,
            'break_ended_at'   => $end,
            'created_at'       => now(),
            'updated_at'       => now(),
        ];
    }
}
