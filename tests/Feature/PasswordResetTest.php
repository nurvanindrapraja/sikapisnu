<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_contains_forgot_password_link(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee(route('password.request'));
        $response->assertSee('Lupa Password?');
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertSee('Lupa Password?');
        $response->assertSee('Kirim Tautan Reset Password');
    }

    public function test_reset_password_link_can_be_requested_and_email_is_sent(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'ahmad.fauzi@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'ahmad.fauzi@example.com',
        ]);

        $response->assertSessionHas('status');

        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($user) {
            return $mail->hasTo('ahmad.fauzi@example.com')
                && $mail->user->id === $user->id
                && ! empty($mail->token)
                && str_contains($mail->resetUrl, $mail->token);
        });

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'ahmad.fauzi@example.com',
        ]);
    }

    public function test_reset_password_request_fails_if_email_is_not_registered(): void
    {
        Mail::fake();

        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'tidak.terdaftar@example.com',
        ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_reset_password_screen_can_be_rendered_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test.reset@example.com',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Atur Ulang Password');
        $response->assertSee($user->email);
        $response->assertSee('Simpan Password Baru');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'andi.santoso@example.com',
            'password' => Hash::make('oldpassword123'),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'andi.santoso@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));

        // Token should be removed after reset
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'andi.santoso@example.com',
        ]);

        // Audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'Reset Password',
        ]);
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'budi.hartono@example.com',
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token-string',
            'email' => 'budi.hartono@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('token');

        $user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $user->password));
    }

    public function test_password_cannot_be_reset_with_mismatched_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'citra.lestari@example.com',
            'password' => Hash::make('oldpassword123'),
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'citra.lestari@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword123',
        ]);

        $response->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check('oldpassword123', $user->password));
    }

    public function test_authenticated_user_is_redirected_from_forgot_password(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
        ]);

        $response = $this->actingAs($user)->get(route('password.request'));

        $response->assertRedirect(route('member.dashboard'));
    }

    public function test_user_can_login_with_new_password_after_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'login.test@example.com',
            'password' => Hash::make('initialpassword'),
            'is_active' => true,
        ]);

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'login.test@example.com',
            'password' => 'brandnewsecret123',
            'password_confirmation' => 'brandnewsecret123',
        ]);

        // Attempt login with new password
        $loginResponse = $this->post(route('login'), [
            'email' => 'login.test@example.com',
            'password' => 'brandnewsecret123',
        ]);

        $this->assertAuthenticatedAs($user);
    }
}
