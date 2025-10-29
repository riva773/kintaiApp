<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\WorkBreak;
use App\Models\AttendanceApproval;
use App\Models\AttendanceApprovalBreak;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CorrectionRequestSuccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($this->user);
    }

    public function test_user_can_submit_correction_request_successfully(): void
    {
        $base = Carbon::parse('2025-10-28 00:00:00', 'Asia/Tokyo');
        $att = Attendance::factory()->create([
            'user_id'      => $this->user->id,
            'work_date'    => $base->copy()->startOfDay(),
            'clock_in_at'  => $base->copy()->setTime(10, 0, 0),
            'clock_out_at' => $base->copy()->setTime(19, 0, 0),
            'status'       => 'finished',
        ]);

        WorkBreak::factory()->create([
            'attendance_id'   => $att->id,
            'sequence_no'     => 1,
            'break_started_at' => $base->copy()->setTime(13, 0, 0),
            'break_ended_at'  => $base->copy()->setTime(13, 30, 0),
        ]);

        $payload = [
            'work-start' => '09:00',
            'work-end'   => '18:00',
            'breaks'     => [
                ['start' => '12:00', 'end' => '12:30'],
                ['start' => '15:00', 'end' => '15:10'],
            ],
            'proposed_remarks' => 'テスト申請：勤務時間と休憩を調整',
        ];

        $res = $this->post(route('attendance.corrections.submit', ['attendance' => $att->id]), $payload);
        $res->assertStatus(302);

        $ap = AttendanceApproval::where('attendance_id', $att->id)
            ->where('user_id', $this->user->id)
            ->where('status', 'pending')
            ->latest('id')->first();

        $this->assertNotNull($ap, 'pending の修正申請が作成されていません');

        $this->assertEquals('09:00', $ap->proposed_clock_in_at?->format('H:i'));
        $this->assertEquals('18:00', $ap->proposed_clock_out_at?->format('H:i'));
        $this->assertEquals('テスト申請：勤務時間と休憩を調整', $ap->proposed_remarks);

        $brs = AttendanceApprovalBreak::where('attendance_approval_id', $ap->id)
            ->orderBy('sequence_no')->get();
        $this->assertCount(2, $brs);
        $this->assertEquals('12:00', $brs[0]->proposed_break_started_at?->format('H:i'));
        $this->assertEquals('12:30', $brs[0]->proposed_break_ended_at?->format('H:i'));
        $this->assertEquals('15:00', $brs[1]->proposed_break_started_at?->format('H:i'));
        $this->assertEquals('15:10', $brs[1]->proposed_break_ended_at?->format('H:i'));

        $this->get('/attendance/detail/' . $att->id)->assertOk();
    }
}
