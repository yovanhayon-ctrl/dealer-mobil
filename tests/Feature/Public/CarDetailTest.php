<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\Category;
use App\Models\Promo;
use App\Models\User;
use App\Support\CreditCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CarDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-26 10:00:00', 'Asia/Jakarta'));
        config()->set('dealer.name', 'Dealer Maju');
        config()->set('dealer.whatsapp', '');
    }

    private function car(string $brand = 'Toyota', string $category = 'MPV', string $name = 'Avanza', array $attributes = []): Car
    {
        return Car::factory()->create([
            'brand_id' => Brand::firstOrCreate(['name' => $brand], ['slug' => str($brand)->slug()])->id,
            'category_id' => Category::firstOrCreate(['name' => $category], ['slug' => str($category)->slug()])->id,
            'name' => $name,
            'slug' => str("{$brand} {$name} 2025")->slug(),
            'year' => 2025,
            'price' => 300_000_000,
            'transmission' => 'automatic',
            'fuel_type' => 'bensin',
            'engine_cc' => 1500,
            'seats' => 7,
            'color' => 'Putih',
            'stock' => 3,
            'description' => 'Mobil keluarga irit.',
            ...$attributes,
        ]);
    }

    private function image(Car $car, string $file, int $sortOrder, bool $primary = false): CarImage
    {
        return CarImage::create([
            'car_id' => $car->id,
            'path' => "cars/{$car->id}/{$file}",
            'is_primary' => $primary,
            'sort_order' => $sortOrder,
        ]);
    }

    private function detail(Car $car): TestResponse
    {
        return $this->get(route('cars.show', $car))->assertOk();
    }

    public function test_detail_mobil_aktif_tampil(): void
    {
        $car = $this->car();

        $response = $this->get('/mobil/toyota-avanza-2025')
            ->assertOk()
            ->assertSee('<title>Toyota Avanza 2025 — Dealer Maju</title>', false)
            ->assertSee('<h1 class="h2 mb-2">Avanza 2025</h1>', false)
            ->assertSee('Toyota · MPV')
            ->assertSeeInOrder(['breadcrumb', route('home'), 'Beranda', route('cars.index'), 'Mobil', 'aria-current="page"', 'Toyota Avanza 2025'], false)
            ->assertSee('Stok:')
            ->assertSee('3 unit');

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
        $this->assertTrue($response->viewData('car')->is($car));
    }

    public function test_slug_tidak_ada_404(): void
    {
        $this->get('/mobil/tidak-ada')->assertNotFound();
    }

    public function test_mobil_nonaktif_404_juga_untuk_admin(): void
    {
        $car = $this->car(attributes: ['is_active' => false]);

        $this->get(route('cars.show', $car))->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->get(route('cars.show', $car))->assertNotFound();
    }

    public function test_galeri_gambar_utama_dulu_dan_thumbnail_lazy(): void
    {
        $car = $this->car();
        $this->image($car, 'depan.jpg', 1);
        $this->image($car, 'utama.jpg', 2, primary: true);
        $this->image($car, 'belakang.jpg', 3);

        $this->detail($car)
            ->assertSee('id="carGallery"', false)
            ->assertSeeInOrder(['utama.jpg', 'depan.jpg', 'belakang.jpg'])
            ->assertSeeInOrder([
                'alt="Toyota Avanza 2025 — foto 1 dari 3"', 'loading="eager"',
                'alt="Toyota Avanza 2025 — foto 2 dari 3"', 'loading="lazy"',
            ], false)
            ->assertSee('data-bs-slide-to="2"', false)
            ->assertSee('aria-label="Tampilkan foto 3"', false)
            ->assertSee('<img src="'.$car->images()->first()->url.'" alt="" loading="lazy">', false)
            ->assertSee('Foto berikutnya');
    }

    public function test_satu_gambar_tanpa_tombol_geser_dan_thumbnail(): void
    {
        $car = $this->car();
        $this->image($car, 'utama.jpg', 1, primary: true);

        $this->detail($car)
            ->assertSee('utama.jpg')
            ->assertDontSee('Foto berikutnya')
            ->assertDontSee('car-gallery-thumbs');
    }

    public function test_placeholder_saat_tanpa_gambar(): void
    {
        $this->detail($this->car())
            ->assertSee('aria-label="Belum ada foto Toyota Avanza 2025"', false)
            ->assertDontSee('id="carGallery"', false)
            ->assertDontSee('og:image', false);
    }

    public function test_harga_promo_dan_semua_promo_aktif(): void
    {
        $car = $this->car(attributes: ['price' => 285_000_000]);
        Promo::factory()->forCar($car, 10_000_000)->create(['title' => 'Diskon Kecil']);
        Promo::factory()->forCar($car, 15_000_000)->create(['title' => 'Diskon Besar']);
        Promo::factory()->forCar($car, 50_000_000)->scheduled()->create(['title' => 'Diskon Terjadwal']);

        $this->detail($car)
            ->assertSeeInOrder(['<del', 'Rp 285.000.000', '</del>', 'Rp 270.000.000'], false)
            ->assertSee('Promo berlaku')
            ->assertSeeInOrder(['Diskon Besar', 'Dipakai di harga', '19 Sep 2026 – 19 Okt 2026', 'Rp 15.000.000', 'Diskon Kecil', 'Rp 10.000.000'])
            ->assertDontSee('Diskon Terjadwal');

        $this->assertSame(1, substr_count($this->detail($car)->getContent(), 'Dipakai di harga'));
    }

    public function test_tanpa_promo_tidak_ada_harga_coret(): void
    {
        $this->detail($this->car())
            ->assertSee('Rp 300.000.000')
            ->assertDontSee('<del', false)
            ->assertDontSee('Promo berlaku');
    }

    public function test_spesifikasi_mobil_baru_dan_bekas(): void
    {
        $new = $this->car();

        $this->detail($new)
            ->assertSeeInOrder(['Spesifikasi', 'Kondisi', 'Baru', 'Transmisi', 'Otomatis', 'Bahan bakar', 'Bensin',
                'Kapasitas mesin', '1.500 cc', 'Jumlah kursi', '7 kursi', 'Warna', 'Putih'])
            ->assertDontSee('Kilometer');

        $used = $this->car('Honda', 'MPV', 'Mobilio', [
            'vehicle_condition' => Car::CONDITION_USED, 'mileage' => 45_000, 'engine_cc' => null, 'color' => null, 'stock' => 0,
        ]);

        $this->detail($used)
            ->assertSeeInOrder(['Kapasitas mesin', '–', 'Warna', '–', 'Kilometer', '45.000 km', 'Stok', 'Habis'])
            ->assertSee('Stok habis')
            ->assertSee('Stok Habis');
    }

    public function test_deskripsi_di_escape_dan_baris_baru_dipertahankan(): void
    {
        $car = $this->car(attributes: ['description' => "Baris satu\n<script>alert(1)</script>"]);

        $this->detail($car)
            ->assertSee('Baris satu<br />', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_deskripsi_kosong_disembunyikan(): void
    {
        $this->detail($this->car(attributes: ['description' => null]))
            ->assertDontSee('id="description-title"', false);
    }

    public function test_tabel_cicilan_sesuai_credit_calculator(): void
    {
        $this->detail($this->car())
            ->assertSee('Ringkasan Cicilan')
            ->assertSeeInOrder(['DP minimum 20%', 'Rp 60.000.000'])
            // Contoh RANCANGAN §5: Rp 300 jt, DP Rp 60 jt, 36 bln → Rp 7.867.000/bln.
            ->assertSeeInOrder(['36 bln', '6%', 'Rp 7.867.000'])
            ->assertSeeInOrder(['60 bln', '7%', 'Rp 5.400.000'])
            ->assertSeeInOrder(['24 bln', '5,5%']);
    }

    public function test_tabel_cicilan_memakai_harga_promo_untuk_semua_tenor(): void
    {
        $car = $this->car(attributes: ['price' => 287_500_000]);
        Promo::factory()->forCar($car, 12_345_000)->create();

        $calculator = app(CreditCalculator::class);
        $price = 287_500_000 - 12_345_000;
        $response = $this->detail($car);

        foreach ($calculator->tenors() as $tenor) {
            $monthly = $calculator->calculate($price, $calculator->minDownPayment($price), $tenor)['monthly_installment'];
            $response->assertSeeInOrder(["{$tenor} bln", 'Rp '.number_format($monthly, 0, ',', '.')]);
        }
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: bool, 3: bool}>
     */
    public static function actionRuleProvider(): array
    {
        return [
            'baru stok ada' => [Car::CONDITION_NEW, 2, true, true],
            'baru stok 0 (unit display)' => [Car::CONDITION_NEW, 0, false, true],
            'bekas stok ada' => [Car::CONDITION_USED, 1, true, true],
            'bekas stok 0 (terjual)' => [Car::CONDITION_USED, 0, false, false],
        ];
    }

    #[DataProvider('actionRuleProvider')]
    public function test_aturan_tombol_ajukan_dan_test_drive(string $condition, int $stock, bool $purchase, bool $testDrive): void
    {
        $car = new Car(['vehicle_condition' => $condition, 'stock' => $stock, 'is_active' => true]);

        $this->assertSame($purchase, $car->canBePurchased());
        $this->assertSame($testDrive, $car->canBeTestDriven());

        $car->is_active = false;
        $this->assertFalse($car->canBePurchased());
        $this->assertFalse($car->canBeTestDriven());
    }

    public function test_tombol_ajukan_dan_test_drive_belum_tampil_selama_route_belum_ada(): void
    {
        $this->detail($this->car())
            ->assertDontSee('Ajukan Pembelian')
            ->assertDontSee('Booking Test Drive')
            ->assertDontSee('Hitung simulasi sendiri');
    }

    public function test_whatsapp_hanya_tampil_jika_nomor_diisi_dengan_pesan_ter_encode(): void
    {
        $car = $this->car();

        $this->detail($car)->assertDontSee('Tanya via WhatsApp')->assertDontSee('wa.me');

        config()->set('dealer.whatsapp', '+62 812-3456-7890');
        $message = 'Halo, saya tertarik dengan Toyota Avanza 2025 (Rp 300.000.000). '.route('cars.show', $car);

        $this->detail($car)
            ->assertSee('Tanya via WhatsApp')
            ->assertSee('href="https://wa.me/6281234567890?text='.rawurlencode($message).'"', false)
            ->assertSee('Toyota%20Avanza%202025', false)
            ->assertSee('http%3A%2F%2F', false)
            ->assertSee('%2Fmobil%2Ftoyota-avanza-2025', false);
    }

    public function test_mobil_serupa_kategori_dulu_lalu_merek(): void
    {
        $car = $this->car();
        $this->car('Honda', 'MPV', 'Mobilio', ['created_at' => now()->subDays(5)]);
        $this->car('Toyota', 'SUV', 'Fortuner', ['created_at' => now()->subDay()]);
        $this->car('Suzuki', 'Hatchback', 'Swift');
        $this->car('Daihatsu', 'MPV', 'Xenia Nonaktif', ['is_active' => false]);

        $response = $this->detail($car)->assertSee('Mobil Serupa');

        $this->assertSame(['Mobilio', 'Fortuner'], $response->viewData('similarCars')->pluck('name')->all());
        $response->assertDontSee('Swift')->assertDontSee('Xenia Nonaktif');
    }

    public function test_mobil_serupa_maksimal_4_tanpa_mobil_itu_sendiri(): void
    {
        $car = $this->car();
        foreach (range(1, 6) as $i) {
            $this->car('Honda', 'MPV', "Serupa {$i}");
        }

        $similar = $this->detail($car)->viewData('similarCars');

        $this->assertCount(4, $similar);
        $this->assertNotContains($car->id, $similar->pluck('id'));
    }

    public function test_section_mobil_serupa_disembunyikan_jika_kosong(): void
    {
        $car = $this->car();
        $this->car('Suzuki', 'Hatchback', 'Swift');

        $this->detail($car)->assertDontSee('Mobil Serupa');
    }

    public function test_meta_description_dipotong_dan_og_image(): void
    {
        $car = $this->car(attributes: ['description' => str_repeat('Mobil nyaman dan irit. ', 20)]);
        $this->image($car, 'utama.jpg', 1, primary: true);

        $html = $this->detail($car)
            ->assertSee('<meta property="og:image" content="'.$car->images()->first()->url.'">', false)
            ->assertSee('<meta property="og:title" content="Toyota Avanza 2025">', false)
            ->assertSee('<link rel="canonical" href="'.route('cars.show', $car).'">', false)
            ->getContent();

        preg_match('/<meta name="description" content="([^"]*)">/', $html, $match);
        $this->assertStringStartsWith('Mobil nyaman dan irit.', $match[1]);
        $this->assertStringEndsWith('...', $match[1]);
        $this->assertLessThanOrEqual(158, mb_strlen($match[1]));
    }

    public function test_meta_description_cadangan_tanpa_deskripsi(): void
    {
        $this->detail($this->car(attributes: ['description' => null]))
            ->assertSee('<meta name="description" content="Toyota Avanza 2025 (Baru), harga Rp 300.000.000 di Dealer Maju.">', false);
    }

    public function test_jumlah_query_tetap_walau_data_bertambah(): void
    {
        $car = $this->car();

        $count = function () use ($car): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->detail($car);

            return count(DB::getQueryLog());
        };

        $this->image($car, 'utama.jpg', 1, primary: true);
        Promo::factory()->forCar($car)->create();
        $this->car('Honda', 'MPV', 'Mobilio');
        $queriesWithLittleData = $count();

        foreach (range(2, 8) as $i) {
            $this->image($car, "foto{$i}.jpg", $i);
        }
        Promo::factory()->forCar($car)->count(4)->create();
        foreach (range(1, 10) as $i) {
            $other = $this->car("Merek {$i}", 'MPV', "Serupa {$i}");
            $this->image($other, 'utama.jpg', 1, primary: true);
            Promo::factory()->forCar($other)->count(2)->create();
        }

        $this->assertSame($queriesWithLittleData, $count());
    }
}
