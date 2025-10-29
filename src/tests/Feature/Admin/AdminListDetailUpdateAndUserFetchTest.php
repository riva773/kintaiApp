<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminListDetailUpdateAndUserFetchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureAdmin::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($this->admin);

        $this->staff = User::factory()->create([
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_admin_attendance_list(): void
    {
        Attendance::factory()->create([
            'user_id'   => $this->staff->id,
            'work_date' => Carbon::parse('2025-10-20', 'Asia/Tokyo')->startOfDay(),
        ]);

        $this->get(route('admin.attendance.index'))->assertOk();
    }

    public function test_admin_staff_list(): void
    {
        $this->get(route('admin.staff.index'))->assertOk();
    }

    public function test_admin_staff_monthly_attendance_list(): void
    {
        Attendance::factory()->create([
            'user_id'   => $this->staff->id,
            'work_date' => Carbon::parse('2025-10-01', 'Asia/Tokyo')->startOfDay(),
        ]);

        $this->get(route('admin.staff.attendance.index', ['id' => $this->staff->id]) . '?ym=2025-10')
            ->assertOk();
    }

    public function test_admin_show_and_update_attendance(): void
    {
        $att = Attendance::factory()->create([
            'user_id'      => $this->staff->id,
            'work_date'    => Carbon::parse('2025-10-28', 'Asia/Tokyo')->startOfDay(),
            'clock_in_at'  => Carbon::parse('2025-10-28 10:00:00', 'Asia/Tokyo'),
            'clock_out_at' => Carbon::parse('2025-10-28 19:00:00', 'Asia/Tokyo'),
        ]);

        $this->get(route('admin.attendance.show', ['id' => $att->id]))->assertOk();

        $payload = [
            'clock_in_at'  => '09:30',
            'clock_out_at' => '18:30',
            'remarks'      => 'テスト更新',
        ];

        $res = $this->patch(route('admin.attendance.update', ['id' => $att->id]), $payload);
        $this->assertTrue(in_array($res->getStatusCode(), [200, 302]), 'Expected 200 or 302');

        $ref = $att->fresh();
        $this->assertEquals('09:30', $ref->clock_in_at?->format('H:i'));
        $this->assertEquals('18:30', $ref->clock_out_at?->format('H:i'));
    }
}
