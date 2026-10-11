<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\Promo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CarCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-26 10:00:00', 'Asia/Jakarta'));
    }

    private function car(string $brand, string $category, string $name, array $attributes = []): Car
    {
        return Car::factory()->create([
            'brand_id' => Brand::firstOrCreate(['name' => $brand], ['slug' => str($brand)->slug()])->id,
            'category_id' => Category::firstOrCreate(['name' => $category], ['slug' => str($category)->slug()])->id,
            'name' => $name,
            'year' => 2025,
            'price' => 300_000_000,
            'seats' => 5,
            'transmission' => 'automatic',
            'fuel_type' => 'bensin',
            ...$attributes,
        ]);
    }

    private function catalog(string $query = ''): TestResponse
    {
        return $this->get('/mobil'.($query !== '' ? "?{$query}" : ''))->assertOk();
    }

    /**
     * Nama mobil yang tampil di halaman (urut sesuai tampilan).
     *
     * @return list<string>
     */
    private function names(TestResponse $response): array
    {
        return $response->viewData('cars')->pluck('name')->all();
    }

    public function test_katalog_hanya_menampilkan_mobil_aktif(): void
    {
        $this->car('Toyota', 'MPV', 'Avanza');
        $this->car('Toyota', 'MPV', 'Rush', ['is_active' => false]);

        $response = $this->catalog()
            ->assertSee('<h1 class="h3 mb-1">Mobil</h1>', false)
            ->assertSee('1</span> mobil ditemukan', false)
            ->assertSee('Avanza 2025')
            ->assertDontSee('Rush');

        $this->assertSame(['Avanza'], $this->names($response));
    }

    public function test_filter_kata_kunci_mencari_nama_dan_merek(): void
    {
        $this->car('Toyota', 'MPV', 'Avanza');
        $this->car('Honda', 'MPV', 'Mobilio');
        $this->car('Daihatsu', 'MPV', 'Xenia Toyota Look');

        $this->assertSame(['Avanza'], $this->names($this->catalog('q=avan')));
        $this->assertEqualsCanonicalizing(['Avanza', 'Xenia Toyota Look'], $this->names($this->catalog('q=toyota')));
        $this->assertSame([], $this->names($this->catalog('q=tidakada')));
    }

    public function test_filter_merek_dan_kategori_memakai_slug(): void
    {
        $this->car('Toyota', 'MPV', 'Avanza');
        $this->car('Toyota', 'Sport Utility', 'Fortuner');
        $this->car('Honda', 'Sport Utility', 'CR-V');

        $this->assertEqualsCanonicalizing(['Avanza', 'Fortuner'], $this->names($this->catalog('merek=toyota')));
        $this->assertEqualsCanonicalizing(['Fortuner', 'CR-V'], $this->names($this->catalog('kategori=sport-utility')));

        $this->catalog('merek=toyota')->assertSee('<title>Mobil Toyota | ', false);
    }

    public function test_filter_kondisi_transmisi_bbm_dan_kursi(): void
    {
        $this->car('Toyota', 'MPV', 'Baru Manual Diesel', ['transmission' => 'manual', 'fuel_type' => 'diesel', 'seats' => 7]);
        Car::factory()->used()->create([
            'brand_id' => Brand::first()->id,
            'category_id' => Category::first()->id,
            'name' => 'Bekas Otomatis Hybrid',
            'transmission' => 'automatic',
            'fuel_type' => 'hybrid',
            'seats' => 5,
        ]);

        $this->assertSame(['Bekas Otomatis Hybrid'], $this->names($this->catalog('kondisi=bekas')));
        $this->assertSame(['Baru Manual Diesel'], $this->names($this->catalog('kondisi=baru')));
        $this->assertSame(['Baru Manual Diesel'], $this->names($this->catalog('transmisi=manual')));
        $this->assertSame(['Bekas Otomatis Hybrid'], $this->names($this->catalog('transmisi=automatic')));
        $this->assertSame(['Baru Manual Diesel'], $this->names($this->catalog('bbm=diesel')));
        $this->assertSame(['Bekas Otomatis Hybrid'], $this->names($this->catalog('bbm=hybrid')));
        $this->assertSame(['Baru Manual Diesel'], $this->names($this->catalog('kursi=7')));
        $this->assertSame([], $this->names($this->catalog('kursi=8')));
    }

    public function test_filter_harga_memakai_harga_setelah_promo_dan_titik_ribuan(): void
    {
        $promo = $this->car('Toyota', 'MPV', 'Promo', ['price' => 300_000_000]);
        Promo::factory()->forCar($promo, 60_000_000)->create(); // harga akhir 240 jt
        $this->car('Toyota', 'MPV', 'Normal', ['price' => 250_000_000]);
        $this->car('Toyota', 'MPV', 'Mahal', ['price' => 500_000_000]);

        $this->assertSame(['Promo'], $this->names($this->catalog('harga_max=245.000.000')));
        $this->assertEqualsCanonicalizing(['Promo', 'Normal'], $this->names($this->catalog('harga_max=250000000')));
        $this->assertEqualsCanonicalizing(['Normal', 'Mahal'], $this->names($this->catalog('harga_min=245.000.000')));
        $this->assertSame(['Normal'], $this->names($this->catalog('harga_min=245000000&harga_max=300000000')));
    }

    public function test_filter_harga_mengabaikan_promo_yang_tidak_berlaku(): void
    {
        $car = $this->car('Toyota', 'MPV', 'Avanza', ['price' => 300_000_000]);
        Promo::factory()->forCar($car, 100_000_000)->scheduled()->create();
        Promo::factory()->forCar($car, 100_000_000)->ended()->create();
        Promo::factory()->forCar($car, 100_000_000)->inactive()->create();
        Promo::factory()->forCar($car, 300_000_000)->create(); // diskon ≥ harga diabaikan

        $this->assertSame([], $this->names($this->catalog('harga_max=299000000')));
        $this->assertSame(['Avanza'], $this->names($this->catalog('harga_min=300000000')));
    }

    public function test_filter_tahun(): void
    {
        $this->car('Toyota', 'MPV', 'Lama', ['year' => 2015]);
        $this->car('Toyota', 'MPV', 'Tengah', ['year' => 2020]);
        $this->car('Toyota', 'MPV', 'Baru', ['year' => 2025]);

        $this->assertEqualsCanonicalizing(['Tengah', 'Baru'], $this->names($this->catalog('tahun_min=2020')));
        $this->assertEqualsCanonicalizing(['Lama', 'Tengah'], $this->names($this->catalog('tahun_max=2020')));
        $this->assertSame(['Tengah'], $this->names($this->catalog('tahun_min=2016&tahun_max=2024')));
    }

    public function test_filter_hanya_promo(): void
    {
        $withPromo = $this->car('Toyota', 'MPV', 'Berpromo');
        Promo::factory()->forCar($withPromo, 10_000_000)->create();

        foreach (['Terjadwal' => 'scheduled', 'Berakhir' => 'ended', 'Nonaktif' => 'inactive'] as $name => $state) {
            $car = $this->car('Toyota', 'MPV', $name);
            Promo::factory()->forCar($car, 10_000_000)->{$state}()->create();
        }

        $tooBig = $this->car('Toyota', 'MPV', 'Diskon Kebesaran', ['price' => 100_000_000]);
        Promo::factory()->forCar($tooBig, 100_000_000)->create();
        $this->car('Toyota', 'MPV', 'Tanpa Promo');

        $this->assertSame(['Berpromo'], $this->names($this->catalog('promo=1')));
    }

    public function test_kombinasi_filter(): void
    {
        $this->car('Toyota', 'SUV', 'Fortuner', ['transmission' => 'automatic', 'year' => 2024, 'price' => 550_000_000]);
        $this->car('Toyota', 'SUV', 'Rush', ['transmission' => 'manual', 'year' => 2024, 'price' => 280_000_000]);
        $this->car('Toyota', 'MPV', 'Avanza', ['transmission' => 'automatic', 'year' => 2024, 'price' => 260_000_000]);
        $this->car('Honda', 'SUV', 'HR-V', ['transmission' => 'automatic', 'year' => 2024, 'price' => 400_000_000]);

        $this->assertSame(
            ['Fortuner'],
            $this->names($this->catalog('merek=toyota&kategori=suv&transmisi=automatic&tahun_min=2024&harga_min=300.000.000')),
        );
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function sortProvider(): array
    {
        return [
            'default terbaru' => ['', ['Baru Dibuat', 'Tengah', 'Lama Dibuat']],
            'terbaru' => ['urut=terbaru', ['Baru Dibuat', 'Tengah', 'Lama Dibuat']],
            // Harga akhir: Lama Dibuat 250 jt, Tengah 270 jt (300 jt − promo 30 jt), Baru Dibuat 280 jt.
            'harga termurah' => ['urut=harga_termurah', ['Lama Dibuat', 'Tengah', 'Baru Dibuat']],
            'harga termahal' => ['urut=harga_termahal', ['Baru Dibuat', 'Tengah', 'Lama Dibuat']],
            'tahun terbaru' => ['urut=tahun_terbaru', ['Tengah', 'Lama Dibuat', 'Baru Dibuat']],
            'tahun terlama' => ['urut=tahun_terlama', ['Baru Dibuat', 'Lama Dibuat', 'Tengah']],
            'km terendah' => ['urut=km_terendah', ['Tengah', 'Baru Dibuat', 'Lama Dibuat']],
        ];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('sortProvider')]
    public function test_urutan(string $query, array $expected): void
    {
        $this->car('Toyota', 'MPV', 'Lama Dibuat', [
            'price' => 250_000_000, 'year' => 2020, 'mileage' => 40_000, 'created_at' => now()->subDays(3),
        ]);
        $middle = $this->car('Toyota', 'MPV', 'Tengah', [
            'price' => 300_000_000, 'year' => 2025, 'mileage' => 0, 'created_at' => now()->subDays(2),
        ]);
        Promo::factory()->forCar($middle, 30_000_000)->create();
        $this->car('Toyota', 'MPV', 'Baru Dibuat', [
            'price' => 280_000_000, 'year' => 2018, 'mileage' => 20_000, 'created_at' => now()->subDay(),
        ]);

        $this->assertSame($expected, $this->names($this->catalog($query)));
    }

    public function test_12_mobil_per_halaman_dan_link_halaman_membawa_filter(): void
    {
        foreach (range(1, 13) as $i) {
            $this->car('Toyota', 'MPV', "Mobil {$i}", ['created_at' => now()->subMinutes($i)]);
        }

        $page1 = $this->catalog('kondisi=baru&merek=tidak-ada');
        $this->assertCount(12, $this->names($page1));
        $page1->assertSee('13</span> mobil ditemukan', false)
            ->assertSee(route('cars.index', ['kondisi' => 'baru', 'page' => 2]))
            // Filter tidak valid tidak ikut dibawa ke link halaman.
            ->assertDontSee('merek=tidak-ada');

        $this->assertSame(['Mobil 13'], $this->names($this->catalog('kondisi=baru&page=2')));
    }

    public function test_chip_filter_aktif_dan_reset(): void
    {
        $this->car('Toyota', 'MPV', 'Avanza');

        $this->catalog()->assertDontSee('Filter aktif');

        $this->catalog('merek=toyota&kondisi=baru&harga_min=100.000.000&harga_max=400000000&promo=1&urut=harga_termurah')
            ->assertSee('Filter aktif')
            ->assertSee('Harga Rp 100.000.000 - Rp 400.000.000')
            ->assertSee('Hanya promo')
            // Chip "Toyota" menghapus merek saja, filter lain & urutan tetap.
            ->assertSee(route('cars.index', [
                'kondisi' => 'baru', 'harga_min' => 100000000, 'harga_max' => 400000000, 'promo' => 1, 'urut' => 'harga_termurah',
            ]))
            // Chip harga menghapus harga min & max sekaligus.
            ->assertSee(route('cars.index', ['merek' => 'toyota', 'kondisi' => 'baru', 'promo' => 1, 'urut' => 'harga_termurah']))
            ->assertSee('href="'.route('cars.index').'" class="small fw-semibold ms-1">Reset</a>', false)
            // Form urutan membawa filter lain lewat hidden input.
            ->assertSee('<input type="hidden" name="merek" value="toyota">', false)
            ->assertSee('data-auto-submit', false);
    }

    public function test_empty_state_saat_tidak_ada_hasil(): void
    {
        $this->car('Toyota', 'MPV', 'Avanza');

        $this->catalog('q=tidakada')
            ->assertSee('Mobil tidak ditemukan')
            ->assertSee('Reset filter')
            ->assertSee('0</span> mobil ditemukan', false);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidQueryProvider(): array
    {
        return [
            'merek tidak dikenal' => ['merek=tidak-ada'],
            'merek bukan slug' => ['merek=Toyota%20Motor'],
            'kategori array' => ['kategori[]=mpv'],
            'harga bukan angka' => ['harga_min=abc&harga_max=xyz'],
            'harga sangat besar' => ['harga_max=99999999999999999999999'],
            'tahun di luar rentang' => ['tahun_min=99999&tahun_max=1800'],
            'urutan tidak dikenal' => ['urut=drop'],
            'kursi array' => ['kursi[]=1'],
            'kursi di luar rentang' => ['kursi=99'],
            'promo bukan 1' => ['promo=x'],
            'kondisi tidak dikenal' => ['kondisi=rusak&transmisi=cvt&bbm=air'],
            'kata kunci array' => ['q[]=avanza'],
        ];
    }

    #[DataProvider('invalidQueryProvider')]
    public function test_query_string_tidak_valid_diabaikan(string $query): void
    {
        $this->car('Toyota', 'MPV', 'Avanza');
        Car::factory()->used()->create([
            'brand_id' => Brand::first()->id,
            'category_id' => Category::first()->id,
            'name' => 'Innova',
            'year' => 2019,
            'price' => 200_000_000,
        ]);

        $response = $this->catalog($query)->assertDontSee('Filter aktif');

        $this->assertEqualsCanonicalizing(['Avanza', 'Innova'], $this->names($response));
    }

    public function test_halaman_di_luar_rentang_menampilkan_empty_state(): void
    {
        $this->car('Toyota', 'MPV', 'Avanza');

        $this->catalog('page=999')->assertSee('Mobil tidak ditemukan');
    }

    public function test_kata_kunci_sangat_panjang_dipotong(): void
    {
        $this->car('Toyota', 'MPV', 'Avanza');

        $response = $this->catalog('q=avanza'.str_repeat('x', 500));

        $this->assertSame([], $this->names($response));
        $this->assertSame(100, mb_strlen($response->viewData('filters')->keyword));
    }

    public function test_min_lebih_besar_dari_max_ditukar(): void
    {
        $this->car('Toyota', 'MPV', 'Murah', ['price' => 150_000_000, 'year' => 2016]);
        $this->car('Toyota', 'MPV', 'Sedang', ['price' => 250_000_000, 'year' => 2020]);
        $this->car('Toyota', 'MPV', 'Mahal', ['price' => 500_000_000, 'year' => 2025]);

        $this->assertSame(['Sedang'], $this->names($this->catalog('harga_min=300000000&harga_max=200000000')));
        $this->assertSame(['Sedang'], $this->names($this->catalog('tahun_min=2022&tahun_max=2018')));
    }

    public function test_jumlah_query_tetap_walau_data_bertambah(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->catalog('urut=harga_termurah&harga_min=1&promo=1');

            return count(DB::getQueryLog());
        };

        $car = $this->car('Toyota', 'MPV', 'Avanza');
        Promo::factory()->forCar($car)->create();
        $queriesWithOneCar = $count();

        foreach (range(1, 15) as $i) {
            $other = $this->car("Merek {$i}", "Kategori {$i}", "Mobil {$i}");
            Promo::factory()->forCar($other)->count(2)->create();
        }

        $this->assertSame($queriesWithOneCar, $count());
    }

    public function test_filter_warna_tanpa_beda_huruf_dan_dropdown_dari_mobil_aktif(): void
    {
        $this->car('Nissan', 'Sport', 'Skyline GT-R', ['color' => 'Biru']);
        $this->car('Nissan', 'SUV', 'Kicks', ['color' => 'Putih']);
        $this->car('Nissan', 'MPV', 'Serena', ['color' => 'Hitam', 'is_active' => false]);
        $this->car('Nissan', 'MPV', 'Livina', ['color' => null]);

        $response = $this->catalog('warna=biru');
        $this->assertSame(['Skyline GT-R'], $this->names($response));
        $response
            ->assertSee('<option value="Biru" selected>Biru</option>', false)
            ->assertSee('<option value="Putih" >Putih</option>', false)
            // Warna mobil nonaktif tidak ditawarkan.
            ->assertDontSee('<option value="Hitam"', false)
            ->assertSee('Warna Biru')
            ->assertSee(route('cars.index'));

        // Warna yang tidak ada diabaikan (semua mobil aktif tampil).
        $this->assertCount(3, $this->names($this->catalog('warna=ungu')));
    }
}
