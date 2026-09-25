<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_bisa_dibuka(): void
    {
        $this->get('/login')->assertOk()->assertSee('Ingat saya');
    }

    public function test_customer_login_diarahkan_ke_beranda(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_customer_kembali_ke_halaman_yang_dituju(): void
    {
        $user = User::factory()->create();

        $this->withSession(['url.intended' => url('/halaman-tujuan')])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(url('/halaman-tujuan'));
    }

    public function test_customer_tidak_diarahkan_ke_area_admin(): void
    {
        $user = User::factory()->create();

        $this->withSession(['url.intended' => url('/admin/dashboard')])
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));
    }

    public function test_admin_login_diarahkan_ke_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_ingat_saya_membuat_cookie_remember(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertCookie(auth()->guard()->getRecallerName());
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_password_salah_ditolak(): void
    {
        $user = User::factory()->create();

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);

        $this->assertGuest();
    }

    public function test_login_dibatasi_setelah_lima_kali_gagal(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah']);
        }

        // Percobaan ke-6, walau password benar, tetap ditolak.
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            session('errors')->first('email'),
        );
        $this->assertGuest();
    }

    public function test_user_yang_sudah_login_tidak_bisa_membuka_halaman_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect(route('home'));

        $this->actingAs(User::factory()->admin()->create())
            ->get('/login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Anda telah keluar.');

        $this->assertGuest();
    }

    public function test_logout_hanya_menerima_post(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/logout')
            ->assertMethodNotAllowed();
    }
}
