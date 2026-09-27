<?php

namespace Tests\Feature\Public;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Navbar & footer layout publik.
 */
class NavbarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Isi <nav> utama saja (footer juga punya link menu).
     */
    private function navbar(TestResponse $response): string
    {
        $html = $response->getContent();
        $start = strpos($html, '<nav class="navbar');

        return substr($html, $start, strpos($html, '</nav>', $start) - $start);
    }

    public function test_tamu_melihat_masuk_daftar_dan_menu_yang_sudah_ada(): void
    {
        $response = $this->get(route('cars.index'))->assertOk();
        $navbar = $this->navbar($response);

        $this->assertStringContainsString('>Masuk', $navbar);
        $this->assertStringContainsString('>Daftar', $navbar);
        $this->assertStringContainsString('href="'.route('home').'"', $navbar);
        $this->assertMatchesRegularExpression('/aria-current="page"\s*>Mobil<\/a>/', $navbar);
        $this->assertStringNotContainsString('Katalog', $navbar);
        // Route belum dibuat: menu disembunyikan.
        $this->assertStringContainsString('href="'.route('test-drives.create').'"', $navbar);
        foreach (['Promo', 'Simulasi Kredit', 'Tentang Kami', 'Kontak', 'Dashboard Admin', 'Keluar', 'Test Drive Saya'] as $label) {
            $this->assertStringNotContainsString($label, $navbar);
        }
    }

    public function test_menu_beranda_aktif_di_beranda(): void
    {
        $navbar = $this->navbar($this->get(route('home'))->assertOk());

        $this->assertMatchesRegularExpression('/aria-current="page"\s*>Beranda<\/a>/', $navbar);
        $this->assertSame(1, substr_count($navbar, 'aria-current="page"'));
    }

    public function test_customer_melihat_nama_dan_keluar_tanpa_dashboard_admin(): void
    {
        $customer = User::factory()->create(['name' => 'Budi Santoso']);

        $navbar = $this->navbar($this->actingAs($customer)->get(route('home'))->assertOk());

        $this->assertStringContainsString('Budi Santoso', $navbar);
        $this->assertStringContainsString('Keluar', $navbar);
        $this->assertStringNotContainsString('Dashboard Admin', $navbar);
        $this->assertStringNotContainsString('>Masuk', $navbar);
    }

    public function test_admin_melihat_link_dashboard_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $navbar = $this->navbar($this->actingAs($admin)->get(route('home'))->assertOk());

        $this->assertStringContainsString('href="'.route('admin.dashboard').'"', $navbar);
        $this->assertSame(2, substr_count($navbar, 'Dashboard Admin')); // menu + dropdown
    }

    public function test_footer_menampilkan_menu_jam_buka_dan_whatsapp(): void
    {
        config()->set('dealer.hours', 'Senin–Sabtu 08.00–17.00');
        config()->set('dealer.whatsapp', '6281234567890');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Jam Operasional')
            ->assertSee('Senin–Sabtu 08.00–17.00')
            ->assertSee('Chat WhatsApp')
            ->assertSee('https://wa.me/6281234567890')
            ->assertSee('<li class="mb-2"><a href="'.route('cars.index').'">Mobil</a></li>', false);
    }

    public function test_footer_tanpa_whatsapp_jika_nomor_kosong(): void
    {
        config()->set('dealer.whatsapp', '');

        $this->get(route('home'))->assertOk()->assertDontSee('wa.me');
    }

    public function test_layout_publik_memuat_script_app(): void
    {
        $this->get(route('home'))->assertOk()->assertSee(asset('js/app.js'));
    }
}
