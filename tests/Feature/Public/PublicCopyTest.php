<?php

namespace Tests\Feature\Public;

use App\Models\Car;
use App\Models\Promo;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Konsistensi teks & tampilan halaman publik: tanpa tanda pisah panjang (— / –),
 * satu nama per tujuan tombol, dan susunan testimoni beranda.
 */
class PublicCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_publik_tanpa_tanda_pisah_panjang(): void
    {
        // Isi .env (mis. DEALER_HOURS) di luar kendali view; dipakai nilai contoh yang rapi.
        config()->set('dealer.hours', 'Senin-Sabtu 08.00-17.00 WIB');

        $car = Car::factory()->create(['engine_cc' => null, 'color' => null]);
        $promo = Promo::factory()->forCar($car)->create();
        Testimonial::factory()->approved()->create();

        $paths = ['/', '/mobil', "/mobil/{$car->slug}", '/promo', "/promo/{$promo->slug}", '/servis',
            '/simulasi-kredit', '/tentang-kami', '/kontak', '/bandingkan', '/login', '/halaman-tidak-ada'];

        foreach ($paths as $path) {
            $html = $this->get($path)->getContent();

            // Komentar Blade tidak ikut dirender, jadi yang tersisa adalah teks yang terlihat/terbaca mesin.
            $this->assertStringNotContainsString('—', $html, "Em dash di {$path}");
            $this->assertStringNotContainsString('–', $html, "En dash di {$path}");
        }
    }

    public function test_beranda_memakai_satu_nama_untuk_simulasi_kredit(): void
    {
        Car::factory()->create();

        $this->get('/')->assertOk()
            ->assertSee('Simulasi Kredit')
            ->assertDontSee('Hitung Cicilan');
    }

    public function test_testimoni_beranda_kutipan_utama_hanya_yang_pertama(): void
    {
        Testimonial::factory()->approved()->count(3)->create();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'testimonial-card-lead'));
        $this->assertSame(3, substr_count($html, 'class="testimonial-quote'));
        $this->assertStringContainsString('class="testimonial-grid"', $html);
        $this->assertStringContainsString('(3 ulasan)', $html);
    }
}
