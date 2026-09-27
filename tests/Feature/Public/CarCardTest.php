<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\Category;
use App\Models\Promo;
use App\Support\CreditCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Kartu mobil (x-car-card), dirender lewat halaman katalog.
 */
class CarCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-26 10:00:00', 'Asia/Jakarta'));
    }

    private function car(array $attributes = []): Car
    {
        return Car::factory()->create([
            'brand_id' => Brand::firstOrCreate(['name' => 'Toyota'], ['slug' => 'toyota'])->id,
            'category_id' => Category::firstOrCreate(['name' => 'MPV'], ['slug' => 'mpv'])->id,
            'name' => 'Avanza',
            'year' => 2025,
            'price' => 300_000_000,
            'transmission' => 'automatic',
            'fuel_type' => 'bensin',
            ...$attributes,
        ]);
    }

    public function test_harga_coret_dan_harga_promo_memakai_diskon_terbesar(): void
    {
        $car = $this->car(['price' => 285_000_000]);
        Promo::factory()->forCar($car, 10_000_000)->create();
        Promo::factory()->forCar($car, 15_000_000)->create();
        Promo::factory()->forCar($car, 50_000_000)->scheduled()->create();

        $this->get(route('cars.index'))
            ->assertOk()
            ->assertSeeInOrder(['Avanza 2025', '<del', 'Rp 285.000.000', '</del>', 'Rp 270.000.000'], false)
            ->assertDontSee('Rp 235.000.000');
    }

    public function test_tanpa_promo_tidak_ada_harga_coret(): void
    {
        $this->car();

        $this->get(route('cars.index'))
            ->assertOk()
            ->assertSee('Rp 300.000.000')
            ->assertDontSee('<del', false);
    }

    public function test_cicilan_mulai_dari_dp_minimum_dan_tenor_terpanjang(): void
    {
        // Rp 300 jt, DP 20% = Rp 60 jt, pokok Rp 240 jt, bunga 7% × 5 tahun = Rp 84 jt → (240 + 84) jt / 60 = Rp 5.400.000.
        $this->car();

        $this->get(route('cars.index'))
            ->assertOk()
            ->assertSeeInOrder(['Cicilan mulai', 'Rp 5.400.000', '/bln']);
    }

    public function test_cicilan_sama_dengan_credit_calculator_untuk_harga_promo(): void
    {
        $car = $this->car(['price' => 287_500_000]);
        Promo::factory()->forCar($car, 12_345_000)->create();

        $calculator = app(CreditCalculator::class);
        $price = 287_500_000 - 12_345_000;
        $expected = $calculator->calculate($price, $calculator->minDownPayment($price), 60)['monthly_installment'];

        $this->get(route('cars.index'))
            ->assertOk()
            ->assertSeeInOrder(['Cicilan mulai', 'Rp '.number_format($expected, 0, ',', '.'), '/bln']);
    }

    public function test_badge_kondisi_promo_dan_stok_habis(): void
    {
        $new = $this->car(['name' => 'Mobil Baru']);
        Promo::factory()->forCar($new, 5_000_000)->create();
        $this->car(['name' => 'Mobil Bekas', 'vehicle_condition' => Car::CONDITION_USED, 'mileage' => 45_000, 'stock' => 0, 'created_at' => now()->subDay()]);

        $this->get(route('cars.index'))
            ->assertOk()
            ->assertSeeInOrder(['badge-condition-new', 'Baru', 'Promo', 'Mobil Baru 2025'], false)
            ->assertSeeInOrder(['badge-condition-used', 'Bekas', 'Stok Habis', 'Mobil Bekas 2025'], false);
    }

    public function test_kilometer_hanya_untuk_mobil_bekas(): void
    {
        $this->car(['name' => 'Mobil Baru']);

        $this->get(route('cars.index'))->assertOk()->assertDontSee(' km</li>', false);

        $this->car(['name' => 'Mobil Bekas', 'vehicle_condition' => Car::CONDITION_USED, 'mileage' => 45_000]);

        $this->get(route('cars.index'))->assertOk()->assertSee('45.000 km');
    }

    public function test_placeholder_saat_tanpa_gambar_dan_lazy_load_saat_ada_gambar(): void
    {
        $withImage = $this->car(['name' => 'Bergambar']);
        CarImage::create(['car_id' => $withImage->id, 'path' => 'cars/1/foto.jpg', 'is_primary' => true, 'sort_order' => 1]);
        $this->car(['name' => 'Polos', 'created_at' => now()->subDay()]);

        $this->get(route('cars.index'))
            ->assertOk()
            ->assertSee('alt="Toyota Bergambar 2025" class="car-card-img" loading="lazy"', false)
            ->assertSee('/storage/cars/1/foto.jpg')
            ->assertSee('aria-label="Belum ada foto Toyota Polos 2025"', false);
    }

    public function test_tombol_detail_dan_judul_menaut_ke_halaman_detail(): void
    {
        $car = $this->car(['slug' => 'toyota-avanza-2025']);
        $url = route('cars.show', $car);

        $this->assertSame(url('/mobil/toyota-avanza-2025'), $url);

        $this->get(route('cars.index'))
            ->assertOk()
            ->assertSee('<a href="'.$url.'" class="stretched-link text-reset text-decoration-none">Avanza 2025</a>', false)
            ->assertSee('<a href="'.$url.'" class="btn btn-outline-primary btn-sm w-100">Detail</a>', false)
            ->assertDontSee('aria-disabled="true">Detail', false);
    }
}
