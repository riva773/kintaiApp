<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class RemarksRequiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_correction_requires_remarks(): void
    {
        $u = User::factory()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($u);

        $att = Attendance::factory()->create([
            'user_id'   => $u->id,
            'work_date' => Carbon::parse('2025-10-28', 'Asia/Tokyo')->startOfDay(),
        ]);

        $payload = [
            'work-start' => '09:00',
            'work-end'   => '18:00',
            'breaks'     => [],
        ];

        $this->post(route('attendance.corrections.submit', ['attendance' => $att->id]), $payload)
            ->assertSessionHasErrors(['proposed_remarks']);
    }

    public function test_admin_update_requires_remarks(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureAdmin::class);

        $admin = User::factory()->create([
            'email'    => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($admin);

        $att = Attendance::factory()->create([
            'user_id'   => $admin->id, 
            'work_date' => Carbon::parse('2025-10-28', 'Asia/Tokyo')->startOfDay(),
        ]);

        $payload = [
            'clock_in_at'  => '09:00',
            'clock_out_at' => '18:00',
        ];

        $this->patch(route('admin.attendance.update', ['id' => $att->id]), $payload)
            ->assertSessionHasErrors(['remarks']);
    }
}
