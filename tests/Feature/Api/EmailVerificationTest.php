<?php

namespace Tests\Feature\Api;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_fires_registered_event_and_queues_verification_email(): void
    {
        Mail::fake();
        Event::fake([Registered::class]);

        $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test Owner',
            'email'                 => 'owner@test.com',
            'password'              => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role'                  => User::ROLE_OWNER,
            'device_name'           => 'test',
        ])->assertCreated()
          ->assertJsonPath('email_verified', false);

        Event::assertDispatched(Registered::class);
    }

    public function test_verify_email_with_valid_signed_url(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.auth.email.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->getJson($url)->assertOk()->assertJsonPath('message', 'Email address verified successfully.');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_verify_email_with_expired_signed_url_fails(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.auth.email.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->getJson($url)->assertStatus(422);
    }

    public function test_verify_email_with_wrong_hash_fails(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'api.auth.email.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'wronghash']
        );

        $this->getJson($url)->assertStatus(422);
    }

    public function test_already_verified_email_returns_200_idempotent(): void
    {
        $user = User::factory()->create(); // verified by default

        $url = URL::temporarySignedRoute(
            'api.auth.email.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->getJson($url)->assertOk()->assertJsonPath('message', 'Email address is already verified.');
    }

    public function test_resend_verification_email_queues_mail(): void
    {
        Mail::fake();

        $user  = User::factory()->unverified()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/v1/auth/email/resend')
             ->assertOk();

        Mail::assertQueued(VerifyEmailMail::class);
    }

    public function test_resend_does_nothing_if_already_verified(): void
    {
        Mail::fake();

        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/v1/auth/email/resend')
             ->assertOk()
             ->assertJsonPath('message', 'Email address is already verified.');

        Mail::assertNothingQueued();
    }
}
