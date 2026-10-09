<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Promo;
use App\Support\CreditCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Halaman simulasi kredit publik (/simulasi-kredit).
 */
class CreditSimulationTest extends TestCase
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

    private function simulate(array $query = []): TestResponse
    {
        return $this->get(route('credit.index', $query))->assertOk();
    }

    public function test_halaman_terbuka_untuk_tamu_tanpa_hasil(): void
    {
        $this->simulate()
            ->assertSee('<title>Simulasi Kredit — ', false)
            ->assertSee('Belum ada simulasi')
            ->assertSee('— Isi harga sendiri —')
            ->assertSee('Nissan Kicks e-Power VL 2025 · Rp 520.000.000')
            ->assertSee('36 bulan (3 tahun) · bunga 6%/tahun')
            ->assertSee('48 bulan (4 tahun) · bunga 6,5%/tahun')
            ->assertSee('tidak terhubung dengan perusahaan leasing')
            ->assertSee(asset('js/credit-simulation.js'), false)
            ->assertSee('data-rates=\'{"12":5,"24":5.5,"36":6,"48":6.5,"60":7}\'', false)
            ->assertViewHas('result', null);
    }

    public function test_contoh_rancangan_300_juta_dp_60_juta_36_bulan(): void
    {
        $this->simulate(['harga' => '300.000.000', 'dp' => '60.000.000', 'tenor' => 36])
            ->assertViewHas('result', fn ($result) => $result['monthly_installment'] === 7_867_000)
            ->assertSee('Rp 7.867.000')
            ->assertSee('Rp 240.000.000')
            ->assertSee('Rp 43.200.000')
            ->assertSee('Rp 343.212.000');
    }

    public function test_mobil_dari_query_string_memakai_harga_promo_dp_minimal_dan_tenor_default(): void
    {
        Promo::factory()->create([
            'car_id' => $this->car->id, 'discount_amount' => 15_000_000, 'is_active' => true,
            'start_date' => '2026-10-01', 'end_date' => '2026-10-31',
        ]);
        $expected = app(CreditCalculator::class)->calculate(505_000_000, 101_000_000, 36);

        $this->simulate(['mobil' => $this->car->slug])
            ->assertViewHas('result', $expected)
            ->assertSee('<option value="nissan-kicks-e-power-vl-2025" data-price="505000000" selected>', false)
            ->assertSee('value="101.000.000"', false)
            ->assertSee('Rp '.number_format($expected['monthly_installment'], 0, ',', '.'))
            ->assertSee('Nissan Kicks e-Power VL 2025')
            ->assertSee('href="'.route('cars.show', $this->car).'"', false)
            ->assertSee('href="'.route('test-drives.create', ['mobil' => $this->car->slug]).'"', false);
    }

    public function test_tabel_perbandingan_semua_tenor_dengan_dp_sama(): void
    {
        $calculator = app(CreditCalculator::class);

        $response = $this->simulate(['mobil' => $this->car->slug, 'dp' => '200.000.000', 'tenor' => 24]);

        $response->assertViewHas('comparison', fn (array $rows) => count($rows) === count($calculator->tenors())
            && collect($rows)->every(fn ($row) => $row['down_payment'] === 200_000_000));
        foreach ($calculator->tenors() as $tenor) {
            $response->assertSee('Rp '.number_format($calculator->calculate(520_000_000, 200_000_000, $tenor)['monthly_installment'], 0, ',', '.'));
        }
        $response->assertSee('class="table-active fw-semibold" data-tenor-row="24"', false);
    }

    public function test_mobil_dipilih_mengabaikan_harga_manual(): void
    {
        $this->simulate(['mobil' => $this->car->slug, 'harga' => '1.000.000'])
            ->assertViewHas('price', 520_000_000)
            ->assertSessionHasNoErrors();
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidInputs(): array
    {
        return [
            'harga terlalu kecil' => [['harga' => '999.999'], 'harga', 'Harga harus antara Rp 1.000.000 dan Rp 10.000.000.000.'],
            'harga terlalu besar' => [['harga' => '10.000.000.001'], 'harga', 'Harga harus antara'],
            'harga nol' => [['harga' => '0'], 'harga', 'Harga harus antara'],
            'dp di bawah 20%' => [['harga' => '300.000.000', 'dp' => '59.999.999'], 'dp', 'Uang muka harus antara Rp 60.000.000 (20%) dan Rp 270.000.000 (90%) dari harga.'],
            'dp di atas 90%' => [['harga' => '300.000.000', 'dp' => '270.000.001'], 'dp', 'Uang muka harus antara'],
            'dp sangat panjang' => [['harga' => '300.000.000', 'dp' => str_repeat('9', 30)], 'dp', 'Uang muka harus antara'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_input_tidak_valid_menampilkan_pesan_tanpa_hasil(array $query, string $field, string $message): void
    {
        $this->simulate($query)
            ->assertViewHas('result', null)
            ->assertSee('is-invalid', false)
            ->assertSee($message)
            ->assertViewHas('errors', fn ($errors) => $errors->has($field));
    }

    public function test_tenor_tidak_dikenal_memakai_default(): void
    {
        $this->simulate(['harga' => '300.000.000', 'tenor' => 18])
            ->assertViewHas('tenor', 36)
            ->assertViewHas('result', fn ($result) => $result['tenor_months'] === 36);
    }

    public function test_mobil_tidak_tersedia_menampilkan_peringatan(): void
    {
        $inactive = Car::factory()->inactive()->create(['slug' => 'mobil-nonaktif-2020']);

        foreach ([$inactive->slug, 'tidak-ada'] as $slug) {
            $this->simulate(['mobil' => $slug])
                ->assertSee('Mobil yang dipilih tidak tersedia.')
                ->assertViewHas('result', null);
        }

        $this->simulate()->assertDontSee('mobil-nonaktif-2020');
    }

    public function test_tombol_whatsapp_berisi_ringkasan_kredit(): void
    {
        config(['dealer.whatsapp' => '6281234567890']);

        $this->simulate(['mobil' => $this->car->slug, 'dp' => '104.000.000', 'tenor' => 60])
            ->assertSee('https://wa.me/6281234567890?text='.rawurlencode('Halo, saya ingin konsultasi kredit Nissan Kicks e-Power VL 2025: DP Rp 104.000.000'), false);
    }

    public function test_link_dari_menu_detail_mobil_dan_promo(): void
    {
        $promo = Promo::factory()->create([
            'car_id' => $this->car->id, 'discount_amount' => 5_000_000, 'is_active' => true,
            'start_date' => '2026-10-01', 'end_date' => '2026-10-31',
        ]);
        $url = route('credit.index', ['mobil' => $this->car->slug]);

        $this->get(route('home'))->assertSee('href="'.route('credit.index').'"', false);
        $this->get(route('cars.show', $this->car))->assertSee('href="'.$url.'"', false);
        $this->get(route('promos.show', $promo))->assertSee('href="'.$url.'"', false);
    }

    public function test_jumlah_query_tetap_walau_mobil_bertambah(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->simulate(['mobil' => $this->car->slug]);

            return count(DB::getQueryLog());
        };

        $before = $count();
        Car::factory()->count(5)->create();
        $this->assertSame($before, $count());
    }
}
