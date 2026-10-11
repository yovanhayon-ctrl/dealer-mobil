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

    public function test_testimoni_beranda_berupa_daftar_dengan_ringkasan_rating(): void
    {
        Testimonial::factory()->approved()->count(3)->create(['rating' => 4]);

        $html = $this->get('/')->assertOk()->getContent();

        // Tanpa kartu, ikon kutip, atau kutipan utama yang dibesarkan.
        $this->assertSame(3, substr_count($html, '<li class="testimonial-item">'));
        $this->assertStringNotContainsString('bi-quote', $html);
        $this->assertStringNotContainsString('testimonial-card', $html);
        $this->assertStringContainsString('4,0<span class="testimonial-score-max">/5</span>', $html);
        $this->assertStringContainsString('Dari 3 ulasan pembeli', $html);
    }

    public function test_whatsapp_berupa_tautan_teks_bukan_tombol_hijau(): void
    {
        config()->set('dealer.whatsapp', '6281234567890');
        $car = Car::factory()->create();
        $promo = Promo::factory()->forCar($car)->create();

        foreach (["/mobil/{$car->slug}", "/promo/{$promo->slug}", '/kontak'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            // Satu warna aksen: tidak ada tombol hijau (btn-success) selain di footer.
            $this->assertStringNotContainsString('btn-success', $html, "Tombol hijau di {$path}");
            $this->assertStringContainsString('https://wa.me/6281234567890', $html);
        }
    }

    public function test_detail_mobil_spesifikasi_grid_dan_aksi_berdampingan(): void
    {
        $car = Car::factory()->create(['color' => 'Putih']);

        $this->get("/mobil/{$car->slug}")->assertOk()
            ->assertSee('<dl class="spec-grid mb-0">', false)
            ->assertSee('<dt>Warna</dt>', false)
            ->assertSee('<div class="car-detail-actions">', false)
            ->assertDontSee('spec-table', false)
            ->assertDontSee('promo-box', false);
    }

    public function test_kolom_samping_detail_promo_tidak_memanjang(): void
    {
        $car = Car::factory()->create();
        $promo = Promo::factory()->forCar($car)->create();
        Promo::factory()->create();

        $html = $this->get("/promo/{$promo->slug}")->assertOk()->getContent();

        $this->assertStringContainsString('car-card card-hover h-100 h-auto', $html);
        $this->assertStringContainsString('promo-card card-hover h-100 overflow-hidden h-auto', $html);
        $this->assertStringNotContainsString('promo-card-img-empty', $html);
    }
}
