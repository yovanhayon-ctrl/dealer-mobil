<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use App\Models\Car;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 16: open redirect, rate limit registrasi, dan header keamanan.
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Open redirect setelah login / daftar ----------

    /**
     * @return array<string, array{string}>
     */
    public static function externalReferers(): array
    {
        return [
            'domain mirip (awalan sama)' => ['http://localhost.evil.example/phishing'],
            'domain lain' => ['https://evil.example/login'],
            'userinfo' => ['http://localhost@evil.example/'],
            'port berbeda' => ['http://localhost:8080/mobil'],
            'skema berbeda' => ['https://localhost/mobil'],
        ];
    }

    #[DataProvider('externalReferers')]
    public function test_login_tidak_mengarahkan_ke_referer_di_luar_aplikasi(string $referer): void
    {
        config(['app.url' => 'http://localhost']);
        $user = User::factory()->create();

        $this->withHeader('Referer', $referer)->get(route('login'))->assertOk();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));
    }

    #[DataProvider('externalReferers')]
    public function test_daftar_tidak_mengarahkan_ke_referer_di_luar_aplikasi(string $referer): void
    {
        $this->withHeader('Referer', $referer)->get(route('register'))->assertOk();

        $this->post(route('register.store'), [
            'name' => 'Budi', 'email' => 'budi@example.test', 'phone' => '081234567890',
            'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123',
        ])->assertRedirect(route('home'));
    }

    public function test_referer_internal_tetap_dipakai_setelah_login(): void
    {
        $user = User::factory()->create();
        $back = route('cars.index', ['merek' => 'nissan']);

        $this->withHeader('Referer', $back)->get(route('login'));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect($back);
    }

    public function test_tujuan_tersimpan_di_luar_aplikasi_diabaikan(): void
    {
        $user = User::factory()->create();

        $this->withSession(['url.intended' => 'https://evil.example/'])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));
    }

    // ---------- Rate limit registrasi ----------

    public function test_registrasi_dibatasi_per_ip(): void
    {
        foreach (range(1, AppServiceProvider::MAX_REGISTRATIONS_PER_MINUTE) as $i) {
            $this->post(route('register.store'), ['name' => ''])->assertSessionHasErrors('name');
        }

        $this->post(route('register.store'), [
            'name' => 'Budi', 'email' => 'budi@example.test', 'phone' => '081234567890',
            'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123',
        ])
            ->assertRedirect(route('register'))
            ->assertSessionHas('error', 'Terlalu banyak percobaan pendaftaran. Coba lagi dalam 1 menit.')
            ->assertSessionMissing('_old_input.password');

        $this->assertDatabaseMissing('users', ['email' => 'budi@example.test']);
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->post(route('register.store'), [
            'name' => 'Budi', 'email' => 'budi@example.test', 'phone' => '081234567890',
            'password' => 'rahasia-123', 'password_confirmation' => 'rahasia-123',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'budi@example.test']);
    }

    // ---------- Header keamanan ----------

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'beranda' => ['/'],
            'katalog' => ['/mobil'],
            'kontak' => ['/kontak'],
            'login' => ['/login'],
            'tidak ditemukan' => ['/halaman-tidak-ada'],
        ];
    }

    #[DataProvider('pages')]
    public function test_header_keamanan_di_setiap_halaman(string $path): void
    {
        $response = $this->get($path);

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
            ->assertHeader('Content-Security-Policy', implode('; ', SecurityHeaders::CONTENT_SECURITY_POLICY))
            ->assertHeaderMissing('X-Powered-By')
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_header_keamanan_di_halaman_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_csp_hanya_mengizinkan_sumber_yang_dipakai(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' https://cdn.jsdelivr.net", $csp);
        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
        $this->assertStringContainsString('frame-src https://www.google.com https://maps.google.com', $csp);
    }

    public function test_view_tidak_memakai_script_inline(): void
    {
        // CSP script-src tanpa 'unsafe-inline': semua script harus file eksternal.
        // Pengecualian: data JSON-LD (type="application/ld+json") yang tidak dieksekusi browser.
        $car = Car::factory()->create();

        foreach (['/', '/mobil', '/simulasi-kredit', '/kontak', "/mobil/{$car->slug}"] as $path) {
            $html = $this->get($path)->getContent();
            preg_match_all('/<script(?![^>]*\b(?:src=|type="application\/ld\+json"))[^>]*>/i', $html, $inline);
            $this->assertSame([], $inline[0], "Script inline ditemukan di {$path}");
            $this->assertDoesNotMatchRegularExpression('/\son(click|change|submit|load|input)=/i', $html, "Event handler inline di {$path}");
        }
    }

    public function test_hsts_hanya_lewat_https(): void
    {
        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
