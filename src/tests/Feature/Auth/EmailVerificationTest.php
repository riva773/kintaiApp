<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_RESEND_VERIFICATION = '/email/verification-notification';
    private const ROUTE_VERIFICATION_NAMED   = 'verification.verify';

    public function test_verification_mail_is_sent_on_register(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        event(new Registered($user));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_verification_mail(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        $this->post(self::ROUTE_RESEND_VERIFICATION)->assertStatus(302);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verify_with_signed_url(): void
    {
        Event::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user);

        $url = URL::temporarySignedRoute(
            self::ROUTE_VERIFICATION_NAMED,
            Carbon::now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->get($url)->assertStatus(302);

        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }
}
