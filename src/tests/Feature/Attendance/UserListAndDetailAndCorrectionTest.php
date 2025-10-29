<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\WorkBreak;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserListAndDetailAndCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_USER_LIST   = '/attendance/list';
    private const ROUTE_USER_DETAIL = '/attendance/detail/';
    private const ROUTE_CORRECTION_SUBMIT = 'attendance.corrections.submit';

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($this->user);
    }

    public function test_user_list_month_prev_next(): void
    {
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::parse('2025-10-15', 'Asia/Tokyo')->startOfDay(),
        ]);
        Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::parse('2025-09-20', 'Asia/Tokyo')->startOfDay(),
        ]);

        $this->get(self::ROUTE_USER_LIST . '?ym=2025-10')->assertOk();
        $this->get(self::ROUTE_USER_LIST . '?ym=2025-09')->assertOk();
        $this->get(self::ROUTE_USER_LIST . '?ym=2025-11')->assertOk();
    }

    public function test_user_detail_shows_attendance_and_breaks(): void
    {
        $att = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::parse('2025-10-28', 'Asia/Tokyo')->startOfDay(),
            'clock_in_at' => Carbon::parse('2025-10-28 10:00:00', 'Asia/Tokyo'),
            'clock_out_at' => Carbon::parse('2025-10-28 19:00:00', 'Asia/Tokyo'),
        ]);
        WorkBreak::factory()->create([
            'attendance_id' => $att->id,
            'sequence_no' => 1,
            'break_started_at' => Carbon::parse('2025-10-28 13:00:00', 'Asia/Tokyo'),
            'break_ended_at'   => Carbon::parse('2025-10-28 13:30:00', 'Asia/Tokyo'),
        ]);

        $this->get(self::ROUTE_USER_DETAIL . $att->id)->assertOk()->assertSee('2025');
    }

    public function test_correction_reject_if_clock_in_after_clock_out(): void
    {
        $att = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::parse('2025-10-28', 'Asia/Tokyo')->startOfDay()
        ]);

        $payload = [
            'work-start' => '20:00',
            'work-end'   => '19:00',
            'breaks'     => [],
            'remarks'    => 'test',
        ];

        $this->post(route(self::ROUTE_CORRECTION_SUBMIT, ['attendance' => $att->id]), $payload)
            ->assertSessionHasErrors(['work-start']);
    }

    public function test_correction_reject_if_break_start_after_clock_out(): void
    {
        $att = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::parse('2025-10-28', 'Asia/Tokyo')->startOfDay()
        ]);

        $payload = [
            'work-start' => '09:00',
            'work-end'   => '18:00',
            'breaks'     => [['start' => '19:00', 'end' => '19:30']],
            'remarks'    => 'test',
        ];

        $this->post(route(self::ROUTE_CORRECTION_SUBMIT, ['attendance' => $att->id]), $payload)
            ->assertSessionHasErrors(['breaks.0.start']);
    }

    public function test_correction_reject_if_break_end_after_clock_out(): void
    {
        $att = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::parse('2025-10-28', 'Asia/Tokyo')->startOfDay()
        ]);

        $payload = [
            'work-start' => '09:00',
            'work-end'   => '18:00',
            'breaks'     => [['start' => '17:40', 'end' => '18:10']],
            'remarks'    => 'test',
        ];

        $this->post(route(self::ROUTE_CORRECTION_SUBMIT, ['attendance' => $att->id]), $payload)
            ->assertSessionHasErrors(['breaks.0.end']);
    }

    public function test_correction_reject_if_break_end_before_start(): void
    {
        $att = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::parse('2025-10-28', 'Asia/Tokyo')->startOfDay()
        ]);

        $payload = [
            'work-start' => '09:00',
            'work-end'   => '18:00',
            'breaks'     => [['start' => '12:30', 'end' => '12:00']],
            'remarks'    => 'test',
        ];

        $this->post(route(self::ROUTE_CORRECTION_SUBMIT, ['attendance' => $att->id]), $payload)
            ->assertSessionHasErrors(['breaks.0.end']);
    }

    public function test_correction_reject_if_breaks_overlap(): void
    {
        $att = Attendance::factory()->create([
            'user_id' => $this->user->id,
            'work_date' => Carbon::parse('2025-10-28', 'Asia/Tokyo')->startOfDay()
        ]);

        $payload = [
            'work-start' => '09:00',
            'work-end'   => '18:00',
            'breaks'     => [
                ['start' => '12:00', 'end' => '13:00'],
                ['start' => '12:30', 'end' => '13:30'],
            ],
            'remarks'    => 'test',
        ];

        $this->post(route(self::ROUTE_CORRECTION_SUBMIT, ['attendance' => $att->id]), $payload)
            ->assertSessionHasErrors();
    }
}
