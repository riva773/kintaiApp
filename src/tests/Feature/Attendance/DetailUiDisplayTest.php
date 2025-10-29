<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\WorkBreak;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DetailUiDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_detail_page_displays_correct_times_and_breaks(): void
    {
        $u = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($u);

        $base = Carbon::parse('2025-10-28 00:00:00', 'Asia/Tokyo');
        $att = Attendance::factory()->create([
            'user_id'      => $u->id,
            'work_date'    => $base->copy()->startOfDay(),
            'clock_in_at'  => $base->copy()->setTime(9, 0, 0),
            'clock_out_at' => $base->copy()->setTime(18, 0, 0),
            'status'       => 'finished',
        ]);
        WorkBreak::factory()->create([
            'attendance_id'   => $att->id,
            'sequence_no'     => 1,
            'break_started_at' => $base->copy()->setTime(12, 0, 0),
            'break_ended_at'  => $base->copy()->setTime(12, 30, 0),
        ]);

        $res = $this->get('/attendance/detail/' . $att->id);
        $res->assertOk();
        $res->assertSee('09:00');
        $res->assertSee('18:00');
        $res->assertSee('12:00');
        $res->assertSee('12:30');
    }
}
