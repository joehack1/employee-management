<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_forgot_password_form(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Reset your password');
    }

    public function test_requesting_a_reset_link_sends_the_branded_notification_and_stores_a_token(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'joel@company.com']);

        $response = $this->post(route('password.email'), ['email' => 'joel@company.com']);

        $response->assertSessionHas('status', 'We have emailed your password reset link.');

        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'joel@company.com']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_an_unregistered_email_cannot_request_a_reset_link(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertSessionHasErrors(['email' => 'We could not find an account with that email address.']);

        Notification::assertNothingSent();

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'nobody@example.com']);
    }

    public function test_the_reset_form_is_shown_for_a_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'joel@company.com']);

        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Set a new password')
            ->assertSee('value="'.$user->email.'"', false);
    }

    public function test_a_valid_token_resets_the_password_signs_in_and_consumes_the_token(): void
    {
        $user = User::factory()->create([
            'email' => 'joel@company.com',
            'password' => 'old-password',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'email' => 'joel@company.com',
            'token' => $token,
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('new-secret', $user->fresh()->password));
        $this->assertFalse(Hash::check('old-password', $user->fresh()->password));

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'joel@company.com']);
    }

    public function test_an_invalid_token_is_rejected_and_the_password_is_unchanged(): void
    {
        $user = User::factory()->create([
            'email' => 'joel@company.com',
            'password' => 'old-password',
        ]);

        $this->post(route('password.update'), [
            'email' => 'joel@company.com',
            'token' => 'not-a-real-token',
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertFalse($this->isAuthenticated());
    }

    public function test_reset_requires_a_confirmed_password(): void
    {
        $user = User::factory()->create(['email' => 'joel@company.com']);

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'email' => 'joel@company.com',
            'token' => $token,
            'password' => 'new-secret',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}