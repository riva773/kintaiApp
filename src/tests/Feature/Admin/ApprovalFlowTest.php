<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceApproval;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Http\Middleware\EnsureAdmin;

class ApprovalFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected Attendance $att;
    protected AttendanceApproval $ap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureAdmin::class);

        $this->admin = User::factory()->create([
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($this->admin);

        $this->staff = User::factory()->create([
            'email'    => 'staff@example.com',
            'password' => Hash::make('password'),
        ]);

        $base = Carbon::parse('2025-10-28 00:00:00', 'Asia/Tokyo');
        $this->att = Attendance::factory()->create([
            'user_id'      => $this->staff->id,
            'work_date'    => $base->copy()->startOfDay(),
            'clock_in_at'  => $base->copy()->setTime(10, 0, 0),
            'clock_out_at' => $base->copy()->setTime(19, 0, 0),
            'status'       => 'finished',
        ]);

        $this->ap = AttendanceApproval::create([
            'attendance_id'        => $this->att->id,
            'user_id'              => $this->staff->id,
            'proposed_clock_in_at' => $base->copy()->setTime(9, 30, 0),
            'proposed_clock_out_at' => $base->copy()->setTime(18, 30, 0),
            'proposed_remarks'     => '管理側承認フローのテスト',
            'status'               => 'pending',
        ]);

        $this->ap->breaks()->create([
            'sequence_no'               => 1,
            'proposed_break_started_at' => $base->copy()->setTime(12, 0, 0),
            'proposed_break_ended_at'   => $base->copy()->setTime(12, 30, 0),
        ]);
    }

    public function test_admin_approval_index_page_loads(): void
    {
        $url = Route::has('admin.approvals.index') ? route('admin.approvals.index') : '/admin/approvals';
        $this->get($url)->assertOk();
    }

    public function test_admin_approval_show_page_loads(): void
    {
        $url = Route::has('admin.approvals.show') ? route('admin.approvals.show', ['id' => $this->ap->id]) : "/admin/approvals/{$this->ap->id}";
        $this->get($url)->assertOk();
    }

    public function test_admin_can_approve_and_apply_to_attendance(): void
    {
        $url = Route::has('admin.approvals.approve') ? route('admin.approvals.approve', ['id' => $this->ap->id]) : "/admin/approvals/{$this->ap->id}/approve";

        $codes = [];
        foreach (['post', 'patch', 'put'] as $m) {
            try {
                $res = $this->{$m}($url, []);
                $codes[] = $res->getStatusCode();
            } catch (\Throwable $e) {
            }
        }
        $this->assertTrue(
            collect($codes)->contains(fn($c) => in_array($c, [200, 302])),
            '承認エンドポイントが 200/302 を返しませんでした。URLやHTTPメソッドを見直してください。'
        );

        $refAtt = $this->att->fresh();
        $refAp  = $this->ap->fresh();

        $this->assertEquals('09:30', $refAtt->clock_in_at?->format('H:i'));
        $this->assertEquals('18:30', $refAtt->clock_out_at?->format('H:i'));
        $this->assertEquals('approved', $refAp->status);
    }
}
