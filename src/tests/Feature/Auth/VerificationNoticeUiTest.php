<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class VerificationNoticeUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_notice_page_is_accessible_for_unverified_user(): void
    {
        $u = User::factory()->create([
            'email'              => 'user@example.com',
            'password'           => Hash::make('password'),
            'email_verified_at'  => null,
        ]);
        $this->actingAs($u);

        $this->get(route('verification.notice'))->assertOk();
    }
}
