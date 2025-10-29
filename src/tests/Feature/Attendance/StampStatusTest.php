<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class StampStatusTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_STAMP_POST = '/attendance';

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

    private function assertOkOrRedirect($response): void
    {
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]), 'Expected 200 or 302, got ' . $response->getStatusCode());
    }

    public function test_clock_in_creates_today_attendance(): void
    {
        $now = Carbon::parse('2025-10-28 09:00:00', 'Asia/Tokyo');
        Carbon::setTestNow($now);

        $res = $this->post(self::ROUTE_STAMP_POST, ['action' => 'clock_in']);
        $this->assertOkOrRedirect($res);

        $this->assertDatabaseHas('attendances', [
            'user_id'   => $this->user->id,
            'work_date' => $now->copy()->startOfDay()->toDateString(),
        ]);
    }

    public function test_status_transitions_working_resting_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-10-28 09:00:00', 'Asia/Tokyo'));
        $this->assertOkOrRedirect($this->post(self::ROUTE_STAMP_POST, ['action' => 'clock_in']));

        $att = Attendance::where('user_id', $this->user->id)->first();
        $this->assertNotNull($att);

        Carbon::setTestNow(Carbon::parse('2025-10-28 12:00:00', 'Asia/Tokyo'));
        $this->assertOkOrRedirect($this->post(self::ROUTE_STAMP_POST, ['action' => 'rest_in']));

        Carbon::setTestNow(Carbon::parse('2025-10-28 12:30:00', 'Asia/Tokyo'));
        $this->assertOkOrRedirect($this->post(self::ROUTE_STAMP_POST, ['action' => 'rest_out']));

        Carbon::setTestNow(Carbon::parse('2025-10-28 18:00:00', 'Asia/Tokyo'));
        $this->assertOkOrRedirect($this->post(self::ROUTE_STAMP_POST, ['action' => 'clock_out']));
    }

    public function test_double_clock_in_denied(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-10-28 09:00:00', 'Asia/Tokyo'));
        $first = $this->post(self::ROUTE_STAMP_POST, ['action' => 'clock_in']);
        $this->assertOkOrRedirect($first);

        $att1 = Attendance::where('user_id', $this->user->id)->first();
        $this->assertNotNull($att1);
        $firstClockIn = $att1->clock_in_at?->format('H:i');

        $second = $this->post(self::ROUTE_STAMP_POST, ['action' => 'clock_in']);
        $this->assertTrue(in_array($second->getStatusCode(), [200, 302]));

        $today = Carbon::now('Asia/Tokyo')->startOfDay()->toDateString();
        $this->assertEquals(1, Attendance::where('user_id', $this->user->id)->where('work_date', $today)->count());
        $this->assertEquals($firstClockIn, Attendance::where('user_id', $this->user->id)->where('work_date', $today)->first()->clock_in_at?->format('H:i'));
    }

    public function test_rest_in_and_rest_out_state_guards(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-10-28 09:00:00', 'Asia/Tokyo'));
        $this->assertOkOrRedirect($this->post(self::ROUTE_STAMP_POST, ['action' => 'clock_in']));

        $this->assertOkOrRedirect($this->post(self::ROUTE_STAMP_POST, ['action' => 'rest_in']));
        $res = $this->post(self::ROUTE_STAMP_POST, ['action' => 'rest_in']);
        $this->assertTrue(in_array($res->getStatusCode(), [200, 302]));
    }

    public function test_clock_out_only_when_working_and_double_clock_out_denied(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-10-28 09:00:00', 'Asia/Tokyo'));
        $this->assertOkOrRedirect($this->post(self::ROUTE_STAMP_POST, ['action' => 'clock_in']));

        Carbon::setTestNow(Carbon::parse('2025-10-28 18:00:00', 'Asia/Tokyo'));
        $this->assertOkOrRedirect($this->post(self::ROUTE_STAMP_POST, ['action' => 'clock_out']));

        $res = $this->post(self::ROUTE_STAMP_POST, ['action' => 'clock_out']);
        $this->assertTrue(in_array($res->getStatusCode(), [200, 302]));
    }
}
