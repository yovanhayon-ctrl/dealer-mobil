<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\Promo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-26 10:00:00', 'Asia/Jakarta'));
        config()->set('dealer.name', 'Dealer Maju');
        config()->set('dealer.whatsapp', '');
    }

    private function car(string $brand, string $category, string $name, array $attributes = []): Car
    {
        return Car::factory()->create([
            'brand_id' => Brand::firstOrCreate(['name' => $brand], ['slug' => str($brand)->slug()])->id,
            'category_id' => Category::firstOrCreate(['name' => $category], ['slug' => str($category)->slug()])->id,
            'name' => $name,
            'year' => 2025,
            ...$attributes,
        ]);
    }

    public function test_beranda_tampil_saat_belum_ada_data(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<h1', false)
            ->assertSee('Dealer Maju')
            ->assertSee('Lihat Mobil')
            ->assertSee('Belum ada mobil')
            ->assertDontSee('Mobil Terbaru')
            ->assertDontSee('Promo Berjalan')
            ->assertDontSee('Jelajahi Mobil');
    }

    public function test_menampilkan_maksimal_8_mobil_aktif_terbaru(): void
    {
        foreach (range(1, 9) as $i) {
            $this->car('Toyota', 'MPV', "Model{$i}", ['created_at' => now()->subDays(10 - $i)]);
        }
        $this->car('Toyota', 'MPV', 'Tersembunyi', ['is_active' => false]);

        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee('Mobil Terbaru')
            ->assertDontSee('Belum ada mobil')
            ->assertSeeInOrder(['Model9', 'Model8', 'Model7', 'Model6', 'Model5', 'Model4', 'Model3', 'Model2'])
            ->assertDontSee('Model1 2025')
            ->assertDontSee('Tersembunyi');

        $this->assertCount(8, $response->viewData('latestCars'));
    }

    public function test_hanya_promo_berjalan_yang_tampil(): void
    {
        $car = $this->car('Toyota', 'MPV', 'Avanza', ['price' => 300_000_000]);
        $inactiveCar = $this->car('Toyota', 'MPV', 'Rush', ['is_active' => false]);

        Promo::factory()->create(['title' => 'Promo Umum Akhir Bulan']);
        Promo::factory()->forCar($car, 15_000_000)->create(['title' => 'Diskon Avanza']);
        Promo::factory()->scheduled()->create(['title' => 'Promo Terjadwal']);
        Promo::factory()->ended()->create(['title' => 'Promo Berakhir']);
        Promo::factory()->inactive()->create(['title' => 'Promo Nonaktif']);
        Promo::factory()->forCar($inactiveCar, 5_000_000)->create(['title' => 'Promo Mobil Nonaktif']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Promo Berjalan')
            ->assertSee('Promo Umum Akhir Bulan')
            ->assertSee('Promo umum')
            ->assertSee('Diskon Avanza')
            ->assertSee('Rp 15.000.000')
            ->assertSee('19 Sep 2026 – 19 Okt 2026')
            ->assertDontSee('Promo Terjadwal')
            ->assertDontSee('Promo Berakhir')
            ->assertDontSee('Promo Nonaktif')
            ->assertDontSee('Promo Mobil Nonaktif');
    }

    public function test_pintasan_merek_dan_kategori_memakai_slug(): void
    {
        $this->car('Toyota', 'Sport Utility', 'Fortuner');
        $this->car('Toyota', 'Sport Utility', 'Rush');
        $this->car('Honda', 'Hatchback', 'Brio', ['is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Jelajahi Mobil')
            ->assertSee(route('cars.index', ['merek' => 'toyota']))
            ->assertSee(route('cars.index', ['kategori' => 'sport-utility']))
            ->assertSeeInOrder(['Toyota', '2'])
            // Merek/kategori tanpa mobil aktif tidak ditampilkan.
            ->assertDontSee(route('cars.index', ['merek' => 'honda']))
            ->assertDontSee(route('cars.index', ['kategori' => 'hatchback']));
    }

    public function test_form_pencarian_menuju_katalog(): void
    {
        $this->car('Toyota', 'MPV', 'Avanza');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('action="'.route('cars.index').'"', false)
            ->assertSee('name="q"', false)
            ->assertSee('<option value="toyota">Toyota</option>', false)
            ->assertSee('<option value="bekas">Bekas</option>', false);
    }

    public function test_tombol_whatsapp_hanya_tampil_jika_nomor_diisi(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('Tanya via WhatsApp')->assertDontSee('wa.me');

        config()->set('dealer.whatsapp', '6281234567890');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tanya via WhatsApp')
            ->assertSee('https://wa.me/6281234567890');
    }

    public function test_meta_title_dan_description(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title>Beranda — Dealer Maju</title>', false)
            ->assertSee('<meta name="description" content="Dealer Maju — mobil baru dan bekas pilihan', false);
    }

    public function test_jumlah_query_tetap_walau_data_bertambah(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->get(route('home'))->assertOk();

            return count(DB::getQueryLog());
        };

        $car = $this->car('Toyota', 'MPV', 'Avanza');
        Promo::factory()->forCar($car)->create();
        $queriesWithLittleData = $count();

        foreach (range(1, 10) as $i) {
            $other = $this->car("Merek {$i}", "Kategori {$i}", "Mobil {$i}");
            Promo::factory()->forCar($other)->count(2)->create();
            Promo::factory()->create();
        }

        $this->assertSame($queriesWithLittleData, $count());
    }
}
