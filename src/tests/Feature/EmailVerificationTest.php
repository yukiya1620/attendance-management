<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_notice_page_is_displayed_for_unverified_user()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
    }

    public function test_verification_email_is_sent_on_register()
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'verify@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'verify@example.com')->first();

        $this->assertNotNull($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_user_can_access_verification_notice_page()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
    }

    public function test_user_can_verify_email_from_signed_url()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verified_user_is_redirected_from_verification_notice_page()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertRedirect();
    }

    public function test_email_verification_page_requires_authentication()
    {
        $response = $this->get('/email/verify');

        $response->assertRedirect('/login');
    }
}