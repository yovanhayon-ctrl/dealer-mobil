<?php

namespace Tests\Feature\Public;

use App\Models\User;
use App\Support\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mode terang/gelap: halaman publik, akun, dan login bisa berganti mode; admin selalu terang.
 */
class ThemeTest extends TestCase
{
    use RefreshDatabase;

    private function themeScript(): string
    {
        return '<script src="'.Asset::url('js/theme.js').'"></script>';
    }

    public function test_halaman_publik_memuat_skrip_mode_sebelum_css_dan_tombol_navbar(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $script = strpos($html, $this->themeScript());
        $this->assertNotFalse($script, 'theme.js harus dimuat di <head>.');
        $this->assertLessThan(strpos($html, 'css/app.css'), $script, 'theme.js dimuat sebelum CSS agar tidak berkedip.');

        $this->assertStringContainsString('data-theme-toggle aria-pressed="false"', $html);
        $this->assertStringContainsString('aria-label="Aktifkan mode gelap"', $html);
        $this->assertStringContainsString('<span class="theme-toggle-label d-xl-none" data-theme-label>Mode gelap</span>', $html);
    }

    public function test_halaman_akun_dan_login_bisa_berganti_mode(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee($this->themeScript(), false)
            ->assertSee('auth-theme-toggle', false);

        $this->actingAs(User::factory()->create())->get(route('account.profile'))->assertOk()
            ->assertSee($this->themeScript(), false)
            ->assertSee('data-theme-toggle', false);
    }

    public function test_admin_selalu_terang_tanpa_tombol_mode(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('data-bs-theme="light"', false)
            ->assertDontSee('js/theme.js', false)
            ->assertDontSee('data-theme-toggle', false);
    }

    public function test_halaman_error_mandiri_ikut_mode(): void
    {
        $this->assertStringContainsString('js/theme.js', view('errors.500')->render());
    }

    public function test_skrip_dan_palet_gelap_tersedia(): void
    {
        $script = file_get_contents(public_path('js/theme.js'));
        $this->assertStringContainsString("'jaf-theme'", $script);
        $this->assertStringContainsString('prefers-color-scheme: dark', $script);

        $css = file_get_contents(public_path('css/app.css'));
        $this->assertStringContainsString('[data-bs-theme="dark"] {', $css);
        $this->assertStringContainsString('--dm-accent-text: #ff5470;', $css);
    }
}
