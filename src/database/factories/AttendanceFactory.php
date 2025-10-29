<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = Carbon::now('Asia/Tokyo')->startOfDay();

        return [
            'user_id'      => User::factory(),
            'status'       => 'finished',
            'work_date'    => $date,
            'clock_in_at'  => $date->copy()->setTime(9, 0, 0),
            'clock_out_at' => $date->copy()->setTime(18, 0, 0),
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
    }

    public function forDate(Carbon $d): self
    {
        return $this->state(fn() => [
            'work_date'    => $d->copy()->startOfDay(),
            'clock_in_at'  => $d->copy()->setTime(9, 0, 0),
            'clock_out_at' => $d->copy()->setTime(18, 0, 0),
        ]);
    }
}
