<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Lupa & reset kata sandi (Phase 16).
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'budi@example.test']);
    }

    private function resetPayload(string $token, array $overrides = []): array
    {
        return [
            'token' => $token,
            'email' => 'budi@example.test',
            'password' => 'kata-sandi-baru',
            'password_confirmation' => 'kata-sandi-baru',
            ...$overrides,
        ];
    }

    public function test_link_lupa_kata_sandi_di_halaman_login_dan_halaman_terbuka(): void
    {
        $this->get(route('login'))->assertSee('href="'.route('password.request').'"', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa Kata Sandi')
            ->assertSee('Kirim Tautan Reset');
    }

    public function test_user_login_tidak_bisa_membuka_lupa_kata_sandi(): void
    {
        $this->actingAs($this->user)->get(route('password.request'))->assertRedirect(route('home'));
    }

    public function test_tautan_reset_dikirim_untuk_email_terdaftar(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => ' BUDI@example.test '])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('success', ForgotPasswordController::GENERIC_MESSAGE);

        Notification::assertSentTo($this->user, ResetPassword::class, function (ResetPassword $notification) {
            $mail = $notification->toMail($this->user);

            return $mail->subject === 'Reset kata sandi Anda'
                && str_contains($mail->actionUrl, '/reset-kata-sandi/'.$notification->token)
                && str_contains($mail->actionUrl, 'email=budi%40example.test');
        });
    }

    public function test_pesan_sama_untuk_email_tidak_terdaftar(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'tidak-ada@example.test'])
            ->assertSessionHas('success', ForgotPasswordController::GENERIC_MESSAGE)
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_validasi_email_lupa_kata_sandi(): void
    {
        $this->post(route('password.email'), ['email' => 'bukan-email'])->assertSessionHasErrors('email');
    }

    public function test_reset_kata_sandi_dengan_token_valid(): void
    {
        $token = Password::createToken($this->user);
        $oldRememberToken = $this->user->remember_token;

        $this->get(route('password.reset', ['token' => $token, 'email' => 'budi@example.test']))
            ->assertOk()
            ->assertSee('value="'.$token.'"', false)
            ->assertSee('value="budi@example.test"', false);

        $this->post(route('password.update'), $this->resetPayload($token))
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', 'Kata sandi Anda berhasil direset. Silakan masuk dengan kata sandi baru.');

        $this->user->refresh();
        $this->assertTrue(Hash::check('kata-sandi-baru', $this->user->password));
        $this->assertNotSame($oldRememberToken, $this->user->remember_token);
        $this->assertGuest();

        // Token sekali pakai.
        $this->post(route('password.update'), $this->resetPayload($token, ['password' => 'lain-lagi-123', 'password_confirmation' => 'lain-lagi-123']))
            ->assertSessionHasErrors(['email' => 'Token reset kata sandi tidak valid.']);
    }

    public function test_token_salah_atau_email_lain_ditolak(): void
    {
        $token = Password::createToken($this->user);
        User::factory()->create(['email' => 'siti@example.test']);

        $this->post(route('password.update'), $this->resetPayload('token-palsu'))
            ->assertSessionHasErrors('email');
        $this->post(route('password.update'), $this->resetPayload($token, ['email' => 'siti@example.test']))
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }

    public function test_token_kedaluwarsa_ditolak(): void
    {
        $token = Password::createToken($this->user);
        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

        $this->post(route('password.update'), $this->resetPayload($token))
            ->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }

    public function test_validasi_kata_sandi_baru(): void
    {
        $token = Password::createToken($this->user);

        $this->post(route('password.update'), $this->resetPayload($token, ['password' => 'pendek', 'password_confirmation' => 'pendek']))
            ->assertSessionHasErrors('password');
        $this->post(route('password.update'), $this->resetPayload($token, ['password_confirmation' => 'beda-sekali']))
            ->assertSessionHasErrors('password');
    }

    public function test_lupa_kata_sandi_dibatasi_per_ip(): void
    {
        Notification::fake();

        foreach (range(1, AppServiceProvider::MAX_PASSWORD_RESETS_PER_MINUTE) as $i) {
            $this->post(route('password.email'), ['email' => "orang{$i}@example.test"])->assertSessionHas('success');
        }

        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'budi@example.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('error', 'Terlalu banyak percobaan. Coba lagi dalam 1 menit.');

        Notification::assertNothingSent();
    }
}
