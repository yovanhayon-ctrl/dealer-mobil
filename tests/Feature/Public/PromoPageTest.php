<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\PromoController;
use App\Models\Brand;
use App\Models\Car;
use App\Models\Promo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Halaman promo publik /promo dan /promo/{slug}.
 */
class PromoPageTest extends TestCase
{
    use RefreshDatabase;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 10:00:00', 'Asia/Jakarta'));
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Kicks e-Power VL', 'year' => 2025, 'slug' => 'nissan-kicks-e-power-vl-2025',
            'price' => 520_000_000, 'stock' => 3,
        ]);
    }

    private function promo(string $title, array $attributes = []): Promo
    {
        return Promo::factory()->create([
            'title' => $title,
            'slug' => str($title)->slug()->value(),
            'car_id' => null,
            'discount_amount' => null,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'is_active' => true,
            'image' => null,
            ...$attributes,
        ]);
    }

    private function carPromo(string $title, array $attributes = []): Promo
    {
        return $this->promo($title, ['car_id' => $this->car->id, 'discount_amount' => 15_000_000, ...$attributes]);
    }

    /**
     * @return list<string>
     */
    private function listedTitles(array $query = []): array
    {
        return $this->get(route('promos.index', $query))->assertOk()->viewData('promos')->pluck('title')->all();
    }

    // ---------- Daftar ----------

    public function test_daftar_hanya_promo_berjalan_yang_terlihat_dan_urut_paling_cepat_berakhir(): void
    {
        $this->promo('Promo Umum Lama', ['end_date' => '2026-10-31']);
        $this->carPromo('Diskon Kicks', ['end_date' => '2026-10-10']);
        $this->promo('Promo Terjadwal', ['start_date' => '2026-10-20']);
        $this->promo('Promo Berakhir', ['end_date' => '2026-10-07']);
        $this->promo('Promo Nonaktif', ['is_active' => false]);
        $inactiveCar = Car::factory()->inactive()->create();
        $this->promo('Promo Mobil Nonaktif', ['car_id' => $inactiveCar->id, 'discount_amount' => 5_000_000]);

        $this->assertSame(['Diskon Kicks', 'Promo Umum Lama'], $this->listedTitles());

        $this->get(route('promos.index'))
            ->assertSee('<title>Promo — ', false)
            ->assertSee('Berakhir dalam 2 hari')
            ->assertSee('Nissan Kicks e-Power VL 2025')
            ->assertSee('hemat')
            ->assertSee('href="'.route('promos.show', 'diskon-kicks').'"', false);
    }

    public function test_label_hari_terakhir(): void
    {
        $this->promo('Promo Hari Ini', ['end_date' => '2026-10-08']);

        $this->get(route('promos.index'))->assertSee('Hari terakhir');
    }

    public function test_filter_jenis(): void
    {
        $this->promo('Promo Umum');
        $this->carPromo('Diskon Kicks');

        $this->assertSame(['Diskon Kicks'], $this->listedTitles(['jenis' => 'mobil']));
        $this->assertSame(['Promo Umum'], $this->listedTitles(['jenis' => 'umum']));
        // Nilai tidak dikenal diabaikan.
        $this->assertCount(2, $this->listedTitles(['jenis' => 'aneh']));

        $this->get(route('promos.index', ['jenis' => 'umum']))
            ->assertSee('aria-current="page" >Promo Umum</a>', false)
            ->assertDontSee('aria-current="page" >Semua</a>', false);
    }

    public function test_empty_state(): void
    {
        $this->get(route('promos.index'))->assertOk()->assertSee('Belum ada promo berjalan');
        $this->get(route('promos.index', ['jenis' => 'mobil']))->assertSee('Tidak ada promo jenis ini yang sedang berjalan.');
    }

    public function test_pagination_9_per_halaman_dan_membawa_filter(): void
    {
        foreach (range(1, PromoController::PER_PAGE + 1) as $i) {
            $this->promo("Promo {$i}", ['end_date' => '2026-10-'.(10 + $i)]);
        }

        $this->assertCount(PromoController::PER_PAGE, $this->listedTitles(['jenis' => 'umum']));
        $this->get(route('promos.index', ['jenis' => 'umum']))
            ->assertSee(e(route('promos.index', ['jenis' => 'umum', 'page' => 2])), false);
        $this->assertSame(['Promo 10'], $this->listedTitles(['jenis' => 'umum', 'page' => 2]));
    }

    public function test_menu_navbar_dan_link_beranda(): void
    {
        $this->promo('Promo Umum');

        $this->get(route('home'))
            ->assertSee('href="'.route('promos.index').'"', false)
            ->assertSee('Semua promo');
    }

    public function test_jumlah_query_daftar_tetap(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->get(route('promos.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $this->carPromo('Diskon 1');
        $before = $count();

        foreach (range(2, 6) as $i) {
            $car = Car::factory()->create(['price' => 300_000_000]);
            $this->promo("Diskon {$i}", ['car_id' => $car->id, 'discount_amount' => 1_000_000]);
        }

        $this->assertSame($before, $count());
    }

    // ---------- Detail ----------

    public function test_detail_promo_mobil_menampilkan_mobil_dan_tombol_aksi(): void
    {
        config(['dealer.whatsapp' => '6281234567890']);
        $promo = $this->carPromo('Diskon Kicks', ['description' => 'Potongan Rp 15 juta.', 'end_date' => '2026-10-12']);
        $this->promo('Promo Lain');

        $this->get(route('promos.show', $promo))
            ->assertOk()
            ->assertSee('<title>Diskon Kicks — ', false)
            ->assertSee('<meta name="description" content="Potongan Rp 15 juta.">', false)
            ->assertSee('Khusus mobil')
            ->assertSee('Berakhir dalam 4 hari')
            ->assertSeeInOrder(['Potongan harga', 'Nissan Kicks e-Power VL 2025', 'hemat', 'Rp 15.000.000'])
            // Kartu mobil memakai harga setelah promo.
            ->assertSee('Rp 505.000.000')
            ->assertSee('href="'.route('cars.show', $this->car).'"', false)
            ->assertSee('href="'.route('test-drives.create', ['mobil' => $this->car->slug]).'"', false)
            ->assertSee('https://wa.me/6281234567890?text=', false)
            ->assertSeeInOrder(['Promo lainnya', 'Promo Lain']);
    }

    public function test_detail_promo_umum(): void
    {
        config(['dealer.whatsapp' => '']);
        $promo = $this->promo('Gratis Servis 3 Tahun');

        $this->get(route('promos.show', $promo))
            ->assertOk()
            ->assertSee('Promo umum')
            ->assertSee('Lihat Semua Mobil')
            ->assertDontSee('Mobil promo')
            ->assertDontSee('Tanya via WhatsApp')
            ->assertSee('Belum ada promo lain yang sedang berjalan.');
    }

    public function test_detail_promo_berakhir_tetap_terbuka_tanpa_tombol_aksi(): void
    {
        $promo = $this->carPromo('Promo Kemarin', ['start_date' => '2026-09-01', 'end_date' => '2026-10-07']);

        $this->get(route('promos.show', $promo))
            ->assertOk()
            ->assertSee('Promo ini sudah berakhir pada 07 Oktober 2026.')
            ->assertSee('Berakhir')
            ->assertDontSee('Lihat Detail Mobil');
    }

    public function test_detail_promo_belum_rilis_atau_mobil_nonaktif_404(): void
    {
        $this->get(route('promos.show', $this->promo('Promo Nonaktif', ['is_active' => false])))->assertNotFound();
        $this->get(route('promos.show', $this->promo('Promo Terjadwal', ['start_date' => '2026-10-20'])))->assertNotFound();
        $this->get('/promo/tidak-ada')->assertNotFound();

        $promo = $this->carPromo('Diskon Kicks');
        $this->car->update(['is_active' => false]);
        $this->get(route('promos.show', $promo))->assertNotFound();
    }

    public function test_kartu_promo_di_beranda_mengarah_ke_detail(): void
    {
        $promo = $this->carPromo('Diskon Kicks');

        $this->get(route('home'))->assertSee('href="'.route('promos.show', $promo).'"', false);
    }
}
