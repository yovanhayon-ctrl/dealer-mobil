<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\Category;
use App\Models\Promo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
            ->assertSee('19 Sep 2026 - 19 Okt 2026')
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
            ->assertSee('<title>Beranda | Dealer Maju</title>', false)
            ->assertSee('<meta name="description" content="Dealer Maju, Nissan Heritage &amp; Performance', false)
            ->assertSee('Nissan Heritage &amp; Performance</p>', false);
    }

    // ---------- Banner hero: mobil unggulan ----------

    private function withPhoto(Car $car): Car
    {
        CarImage::create(['car_id' => $car->id, 'path' => "cars/{$car->id}/foto.jpg", 'is_primary' => true, 'sort_order' => 1]);

        return $car;
    }

    public function test_hero_menampilkan_nissan_terbaru_yang_punya_foto(): void
    {
        config(['dealer.hero_videos' => []]);
        $this->withPhoto($this->car('Nissan', 'Sport', 'Skyline GT-R', ['created_at' => now()->subDays(3)]));
        $this->withPhoto($this->car('Subaru', 'Sedan', 'Impreza WRX', ['created_at' => now()->subDay()]));
        $this->car('Nissan', 'MPV', 'Livina Tanpa Foto', ['created_at' => now()]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('aria-label="Mobil unggulan: Nissan Skyline GT-R 2025"', false)
            ->assertSee('hero-feature-badge', false)
            ->assertDontSee('Mobil unggulan: Subaru', false)
            ->assertDontSee('Mobil unggulan: Nissan Livina Tanpa Foto', false);
    }

    public function test_hero_memakai_merek_lain_bila_tidak_ada_nissan_berfoto(): void
    {
        config(['dealer.hero_videos' => []]);
        $this->withPhoto($this->car('Toyota', 'Sport', 'Sprinter Trueno AE86'));

        $this->get(route('home'))
            ->assertSee('aria-label="Mobil unggulan: Toyota Sprinter Trueno AE86 2025"', false);
    }

    public function test_hero_memakai_mobil_unggulan_pilihan_admin(): void
    {
        config(['dealer.hero_videos' => []]);
        $this->withPhoto($this->car('Nissan', 'Sport', 'Skyline GT-R', ['created_at' => now()]));
        // Lebih lama & bukan Nissan, tetapi dipilih admin.
        $this->withPhoto($this->car('Toyota', 'Sport', 'Supra RZ', ['created_at' => now()->subYear(), 'is_featured' => true]));
        // Unggulan tetapi nonaktif / tanpa foto: diabaikan.
        $this->withPhoto($this->car('Honda', 'Sport', 'NSX', ['is_featured' => true, 'is_active' => false]));
        $this->car('Mazda', 'Sport', 'RX-7', ['is_featured' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('aria-label="Mobil unggulan: Toyota Supra RZ 2025"', false);
    }

    public function test_unggulan_tanpa_foto_kembali_ke_pilihan_otomatis(): void
    {
        config(['dealer.hero_videos' => []]);
        $this->withPhoto($this->car('Nissan', 'Sport', 'Skyline GT-R'));
        $this->car('Mazda', 'Sport', 'RX-7', ['is_featured' => true]);

        $this->get(route('home'))
            ->assertSee('aria-label="Mobil unggulan: Nissan Skyline GT-R 2025"', false);
    }

    public function test_hero_tanpa_kartu_bila_tidak_ada_mobil_berfoto(): void
    {
        $this->car('Nissan', 'MPV', 'Livina');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Dealer Maju')
            ->assertDontSee('hero-feature', false);
    }

    // ---------- Video latar hero ----------

    /**
     * File video sementara di public/ (dihapus lagi setelah test), agar test tidak bergantung pada video asli.
     *
     * @param  list<string>  $paths
     */
    private function withTemporaryVideos(array $paths, callable $callback): void
    {
        foreach ($paths as $path) {
            File::ensureDirectoryExists(dirname(public_path($path)));
            File::put(public_path($path), 'video-uji');
        }

        try {
            $callback();
        } finally {
            File::delete(array_map(fn ($path) => public_path($path), $paths));
            File::deleteDirectory(public_path('videos-uji'));
        }
    }

    public function test_hero_memakai_video_dan_kartu_unggulan_disembunyikan(): void
    {
        $this->withTemporaryVideos(['videos-uji/satu.mp4', 'videos-uji/dua.mp4'], function () {
            $this->assertHeroWithVideos();
        });
    }

    private function assertHeroWithVideos(): void
    {
        config(['dealer.hero_videos' => ['videos-uji/satu.mp4', 'videos-uji/dua.mp4']]);
        $this->withPhoto($this->car('Nissan', 'Sport', 'Skyline GT-R'));

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('class="home-hero py-5 has-video"', false)
            ->assertDontSee('hero-video-toggle', false)
            ->assertDontSee('hero-feature', false);

        preg_match("/data-hero-videos='([^']+)'/", $response->getContent(), $match);
        $sources = json_decode(html_entity_decode($match[1]), true, flags: JSON_THROW_ON_ERROR);
        $this->assertCount(2, $sources);
        $this->assertMatchesRegularExpression('#/videos-uji/satu\.mp4\?v=\d+$#', $sources[0]);
        $this->assertMatchesRegularExpression('#/videos-uji/dua\.mp4\?v=\d+$#', $sources[1]);
    }

    public function test_video_tidak_valid_diabaikan_dan_kembali_ke_kartu_unggulan(): void
    {
        config(['dealer.hero_videos' => ['videos-uji/tidak-ada.mp4', '../.env', 'videos-uji/video.webm', 'css/app.css']]);
        $this->withPhoto($this->car('Nissan', 'Sport', 'Skyline GT-R'));

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('has-video', false)
            ->assertDontSee('data-hero-videos', false)
            ->assertSee('aria-label="Mobil unggulan: Nissan Skyline GT-R 2025"', false);
    }

    public function test_mobil_terbaru_bisa_digeser_di_hp(): void
    {
        $this->car('Nissan', 'Sport', 'Skyline GT-R');
        $this->car('Nissan', 'MPV', 'Livina');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('class="row g-4 car-scroller"', false)
            ->assertSee('Geser untuk melihat mobil lainnya');
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
