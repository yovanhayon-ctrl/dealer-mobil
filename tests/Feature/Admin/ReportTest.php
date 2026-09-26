<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\PurchaseRequest;
use App\Models\TestDrive;
use App\Models\User;
use App\Reports\AdminReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-26 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create();
    }

    private function car(string $brand, string $category, string $name, array $attributes = []): Car
    {
        return Car::factory()->create([
            'brand_id' => Brand::firstOrCreate(['name' => $brand], ['slug' => str($brand)->slug()])->id,
            'category_id' => Category::firstOrCreate(['name' => $category], ['slug' => str($category)->slug()])->id,
            'name' => $name,
            'year' => 2025,
            'stock' => 5,
            ...$attributes,
        ]);
    }

    private function purchase(Car $car, string $status, int $price, string $createdAt, string $method = 'cash'): PurchaseRequest
    {
        $factory = PurchaseRequest::factory()->recycle($this->customer)->recycle($car);

        return ($method === 'credit' ? $factory->credit() : $factory)->create([
            'car_price' => $price,
            'status' => $status,
            'created_at' => $createdAt,
        ]);
    }

    private function makeTestDrive(Car $car, string $status, string $date): TestDrive
    {
        return TestDrive::factory()->recycle($this->customer)->recycle($car)->create([
            'status' => $status,
            'preferred_date' => $date,
        ]);
    }

    /**
     * Data September 2026 + data di luar periode (31 Agustus 23:59:59 dan 1 Oktober 00:00:00).
     *
     * @return array{avanza: Car, hrv: Car, innova: Car}
     */
    private function seedSeptember(): array
    {
        $avanza = $this->car('Toyota', 'MPV', 'Avanza');
        $hrv = $this->car('Honda', 'SUV', 'HR-V');
        $innova = $this->car('Toyota', 'MPV', 'Innova');

        // Di dalam periode (termasuk tepat di batas awal & akhir hari).
        $this->purchase($avanza, 'approved', 270_000_000, '2026-09-01 00:00:00');
        $this->purchase($avanza, 'completed', 280_000_000, '2026-09-10 09:00:00', 'credit');
        $this->purchase($hrv, 'completed', 400_000_000, '2026-09-30 23:59:59');
        $this->purchase($innova, 'approved', 600_000_000, '2026-09-15 13:00:00');
        $this->purchase($hrv, 'pending', 400_000_000, '2026-09-20 08:00:00', 'credit');
        $this->purchase($avanza, 'rejected', 285_000_000, '2026-09-05 10:00:00');
        $this->purchase($avanza, 'cancelled', 285_000_000, '2026-09-06 10:00:00', 'credit');
        $this->purchase($hrv, 'processing', 400_000_000, '2026-09-25 10:00:00');

        // Di luar periode.
        $this->purchase($avanza, 'approved', 270_000_000, '2026-08-31 23:59:59');
        $this->purchase($hrv, 'completed', 400_000_000, '2026-10-01 00:00:00');

        $this->makeTestDrive($avanza, 'completed', '2026-09-01');
        $this->makeTestDrive($hrv, 'completed', '2026-09-12');
        $this->makeTestDrive($innova, 'confirmed', '2026-09-28');
        $this->makeTestDrive($avanza, 'cancelled', '2026-09-30');
        $this->makeTestDrive($avanza, 'completed', '2026-08-31');
        $this->makeTestDrive($hrv, 'pending', '2026-10-01');

        return compact('avanza', 'hrv', 'innova');
    }

    private function report(array $query = []): AdminReport
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.reports.index', $query))
            ->assertOk()
            ->viewData('report');
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function csvRows(TestResponse $response): array
    {
        $content = substr($response->streamedContent(), 3);

        return array_map(
            fn ($line) => str_getcsv($line, ';', '"', ''),
            explode("\n", rtrim($content, "\n")),
        );
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/laporan')->assertRedirect(route('login'));
        $this->get('/admin/laporan/export')->assertRedirect(route('login'));
    }

    public function test_customer_mendapat_403(): void
    {
        $this->actingAs($this->customer);
        $this->get('/admin/laporan')->assertForbidden();
        $this->get('/admin/laporan/export')->assertForbidden();
    }

    public function test_default_periode_bulan_ini(): void
    {
        $report = $this->report();

        $this->assertSame('2026-09-01 00:00:00', $report->period->from->toDateTimeString());
        $this->assertSame('2026-09-30 23:59:59', $report->period->to->toDateTimeString());

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertSee('Periode: 1 September 2026 – 30 September 2026')
            ->assertSee('Cetak')
            ->assertSee('data-print', false)
            ->assertSee('href="'.e(route('admin.reports.export', ['periode' => 'bulan-ini'])).'"', false);
    }

    public function test_pilihan_cepat_dan_rentang_kustom(): void
    {
        $lastMonth = $this->report(['periode' => 'bulan-lalu'])->period;
        $this->assertSame(['2026-08-01', '2026-08-31'], [$lastMonth->from->toDateString(), $lastMonth->to->toDateString()]);

        $thisYear = $this->report(['periode' => 'tahun-ini'])->period;
        $this->assertSame(['2026-01-01', '2026-12-31'], [$thisYear->from->toDateString(), $thisYear->to->toDateString()]);

        $custom = $this->report(['periode' => 'kustom', 'dari' => '2026-09-10', 'sampai' => '2026-09-20'])->period;
        $this->assertSame(['2026-09-10', '2026-09-20'], [$custom->from->toDateString(), $custom->to->toDateString()]);

        // Tanggal tanpa `periode` dianggap kustom; satu hari penuh boleh.
        $oneDay = $this->report(['dari' => '2026-09-15', 'sampai' => '2026-09-15'])->period;
        $this->assertSame('kustom', $oneDay->preset);
        $this->assertSame('2026-09-15 23:59:59', $oneDay->to->toDateTimeString());

        // Tepat 1 tahun (26 Sep 2025 – 25 Sep 2026) masih diterima.
        $this->report(['periode' => 'kustom', 'dari' => '2025-09-26', 'sampai' => '2026-09-25']);
    }

    /**
     * @return array<string, array{array<string, string>, string, string}>
     */
    public static function invalidPeriodProvider(): array
    {
        return [
            'tanggal tidak valid' => [['periode' => 'kustom', 'dari' => '2026-02-30', 'sampai' => '2026-03-10'], 'dari', 'Tanggal dari tidak valid.'],
            'format salah' => [['periode' => 'kustom', 'dari' => '01/09/2026', 'sampai' => '2026-09-10'], 'dari', 'Tanggal dari tidak valid.'],
            'sampai sebelum dari' => [['periode' => 'kustom', 'dari' => '2026-09-10', 'sampai' => '2026-09-09'], 'sampai', 'Tanggal sampai tidak boleh sebelum tanggal dari.'],
            'lebih dari 1 tahun' => [['periode' => 'kustom', 'dari' => '2025-09-26', 'sampai' => '2026-09-26'], 'sampai', 'Rentang laporan maksimal 1 tahun.'],
            'kustom tanpa tanggal' => [['periode' => 'kustom'], 'dari', 'Tanggal dari wajib diisi.'],
            'periode tidak dikenal' => [['periode' => 'minggu-ini'], 'periode', 'Pilihan periode tidak dikenal.'],
        ];
    }

    #[DataProvider('invalidPeriodProvider')]
    public function test_validasi_periode(array $query, string $field, string $message): void
    {
        foreach (['admin.reports.index', 'admin.reports.export'] as $route) {
            $this->actingAs($this->admin)->get(route($route, $query))
                ->assertRedirect(route('admin.reports.index'))
                ->assertSessionHasErrors([$field => $message]);
        }
    }

    public function test_pesan_validasi_tampil_dan_laporan_kembali_ke_bulan_ini(): void
    {
        $this->actingAs($this->admin)
            ->followingRedirects()
            ->get(route('admin.reports.index', ['periode' => 'kustom', 'dari' => '2026-09-10', 'sampai' => '2026-09-01']))
            ->assertOk()
            ->assertSee('Tanggal sampai tidak boleh sebelum tanggal dari.')
            ->assertSee('Menampilkan laporan bulan ini.')
            ->assertSee('Periode: 1 September 2026 – 30 September 2026');
    }

    public function test_ringkasan_pengajuan_sesuai_data_dan_batas_periode(): void
    {
        $this->seedSeptember();

        $summary = $this->report()->purchaseSummary();

        $this->assertSame(8, $summary['total']);
        $this->assertSame([
            'pending' => 1, 'processing' => 1, 'approved' => 2,
            'rejected' => 1, 'completed' => 2, 'cancelled' => 1,
        ], $summary['statuses']);
        $this->assertSame(5, $summary['cash']);
        $this->assertSame(3, $summary['credit']);
        $this->assertSame(4, $summary['units_sold'], 'Terjual = approved + completed.');
        $this->assertSame(1_550_000_000, $summary['sales_value']);

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertSeeInOrder(['Total Pengajuan', 'Unit Terjual', 'Nilai Penjualan', 'Rp 1.550.000.000']);
    }

    public function test_periode_lain_tidak_memasukkan_data_di_luar_rentang(): void
    {
        $this->seedSeptember();

        $august = $this->report(['periode' => 'bulan-lalu']);
        $this->assertSame(1, $august->purchaseSummary()['total']);
        $this->assertSame(270_000_000, $august->purchaseSummary()['sales_value']);
        $this->assertSame(['completed' => 1], array_filter($august->testDriveSummary()['statuses']));

        $oneDay = $this->report(['dari' => '2026-09-30', 'sampai' => '2026-09-30']);
        $this->assertSame(1, $oneDay->purchaseSummary()['total']);
        $this->assertSame(400_000_000, $oneDay->purchaseSummary()['sales_value']);

        $this->assertSame(10, $this->report(['periode' => 'tahun-ini'])->purchaseSummary()['total']);
    }

    public function test_penjualan_per_merek_kategori_dan_terlaris(): void
    {
        $this->seedSeptember();
        $report = $this->report();

        $this->assertEquals([
            (object) ['name' => 'Toyota', 'units' => 3, 'value' => 1_150_000_000],
            (object) ['name' => 'Honda', 'units' => 1, 'value' => 400_000_000],
        ], $report->salesByBrand()->all());

        $this->assertEquals([
            (object) ['name' => 'MPV', 'units' => 3, 'value' => 1_150_000_000],
            (object) ['name' => 'SUV', 'units' => 1, 'value' => 400_000_000],
        ], $report->salesByCategory()->all());

        // Urut unit terbanyak, lalu nilai; status non-terjual tidak dihitung.
        $this->assertSame(
            [['Toyota Avanza 2025', 2, 550_000_000], ['Toyota Innova 2025', 1, 600_000_000], ['Honda HR-V 2025', 1, 400_000_000]],
            $report->topCars()->map(fn ($car) => [$car->name, $car->units, $car->value])->all(),
        );

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertSeeInOrder(['Penjualan per Merek', 'Toyota', 'Rp 1.150.000.000', 'Honda', 'Rp 400.000.000'])
            ->assertSeeInOrder(['5 Mobil Terlaris', 'Toyota Avanza 2025', 'Toyota Innova 2025', 'Honda HR-V 2025']);
    }

    public function test_mobil_terlaris_maksimal_lima(): void
    {
        foreach (range(1, 6) as $i) {
            $car = $this->car('Merek '.$i, 'Kategori', 'Mobil '.$i);
            foreach (range(1, $i) as $unit) {
                $this->purchase($car, 'completed', 100_000_000, '2026-09-10 10:00:00');
            }
        }

        $top = $this->report()->topCars();

        $this->assertCount(5, $top);
        $this->assertSame(['Merek 6 Mobil 6 2025', 'Merek 5 Mobil 5 2025'], $top->take(2)->pluck('name')->all());
        $this->assertNotContains('Merek 1 Mobil 1 2025', $top->pluck('name')->all());
    }

    public function test_ringkasan_test_drive_berdasarkan_tanggal_jadwal(): void
    {
        $this->seedSeptember();

        $summary = $this->report()->testDriveSummary();

        $this->assertSame(4, $summary['total']);
        $this->assertSame(['pending' => 0, 'confirmed' => 1, 'completed' => 2, 'cancelled' => 1], $summary['statuses']);
        $this->assertSame(50.0, $summary['completion_rate']);

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertSeeInOrder(['Test Drive Selesai', '50,0%'])
            ->assertSeeInOrder(['Test Drive per Status', 'Selesai: 50,0%']);
    }

    public function test_stok_menipis_hanya_mobil_aktif_dengan_stok_maksimal_satu(): void
    {
        $soldOut = $this->car('Toyota', 'SUV', 'Fortuner', ['stock' => 0]);
        $lastUnit = $this->car('Mitsubishi', 'SUV', 'Pajero', ['stock' => 1, 'vehicle_condition' => 'bekas', 'mileage' => 50_000]);
        $this->car('Honda', 'SUV', 'CR-V', ['stock' => 2]);
        $this->car('Daihatsu', 'LCGC', 'Sigra', ['stock' => 0, 'is_active' => false]);

        $report = $this->report();

        $this->assertSame([$soldOut->id, $lastUnit->id], $report->lowStockCars()->pluck('id')->all());

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertSeeInOrder(['Stok Menipis', 'Toyota Fortuner 2025', 'Stok Habis', 'Mitsubishi Pajero 2025', 'Bekas'])
            ->assertSee('href="'.route('admin.cars.edit', $soldOut).'"', false)
            ->assertSee('href="'.route('admin.cars.edit', $lastUnit).'"', false)
            ->assertDontSee('CR-V')
            ->assertDontSee('Sigra');
    }

    public function test_tampilan_kosong_jika_belum_ada_data(): void
    {
        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Belum ada pengajuan')
            ->assertSee('Belum ada penjualan')
            ->assertSee('Belum ada test drive')
            ->assertSee('Stok aman')
            ->assertSee('Rp 0')
            ->assertSee('0,0%');
    }

    public function test_jumlah_query_tetap_walau_data_bertambah(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $car = $this->car('Toyota', 'MPV', 'Avanza', ['stock' => 1]);
        $this->purchase($car, 'approved', 270_000_000, '2026-09-10 10:00:00');
        $this->makeTestDrive($car, 'completed', '2026-09-10');
        $queriesWithLittleData = $count();

        foreach (range(1, 8) as $i) {
            $other = $this->car('Merek '.$i, 'Kategori '.$i, 'Mobil '.$i, ['stock' => $i % 2]);
            $this->purchase($other, 'completed', 100_000_000, '2026-09-11 10:00:00', 'credit');
            $this->makeTestDrive($other, 'confirmed', '2026-09-12');
        }

        $this->assertSame($queriesWithLittleData, $count());
    }

    public function test_export_csv_berisi_angka_yang_sama_dan_menghormati_periode(): void
    {
        $this->seedSeptember();

        $response = $this->actingAs($this->admin)->get(route('admin.reports.export'));

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('laporan-2026-09-01_2026-09-30.csv', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $response->streamedContent());

        $rows = $this->csvRows($response);
        $this->assertContains(['Periode', '1 September 2026 – 30 September 2026'], $rows);
        $this->assertContains(['Total pengajuan', '8'], $rows);
        $this->assertContains(['Unit terjual', '4'], $rows);
        $this->assertContains(['Nilai penjualan (Rp)', '1550000000'], $rows);
        $this->assertContains(['Toyota', '3', '1150000000'], $rows);
        $this->assertContains(['SUV', '1', '400000000'], $rows);
        $this->assertContains(['1', 'Toyota Avanza 2025', '2', '550000000'], $rows);
        $this->assertContains(['Selesai', '2'], $rows);
        $this->assertContains(['Persentase selesai (%)', '50'], $rows);

        $august = $this->csvRows($this->actingAs($this->admin)->get(route('admin.reports.export', ['periode' => 'bulan-lalu'])));
        $this->assertContains(['Total pengajuan', '1'], $august);
        $this->assertContains(['Nilai penjualan (Rp)', '270000000'], $august);
    }

    public function test_export_csv_melindungi_dari_csv_injection(): void
    {
        $car = $this->car('=HYPERLINK("http://jahat.test","klik")', '@SUM(1+1)', '+Mobil Formula', ['stock' => 1]);
        $this->car("\tTab Brand", "\rCR Kategori", '-Minus', ['stock' => 0]);
        $this->purchase($car, 'completed', 250_000_000, '2026-09-10 10:00:00');

        $rows = $this->csvRows($this->actingAs($this->admin)->get(route('admin.reports.export')));
        // Baris kosong pemisah bagian menghasilkan sel null saat di-parse.
        $cells = array_filter(array_merge(...$rows), fn ($cell) => $cell !== null);

        $this->assertContains(['\'=HYPERLINK("http://jahat.test","klik")', '1', '250000000'], $rows);
        $this->assertContains(['\'@SUM(1+1)', '1', '250000000'], $rows);
        $this->assertContains(['1', '\'=HYPERLINK("http://jahat.test","klik") +Mobil Formula 2025', '1', '250000000'], $rows);
        $this->assertContains(["'\tTab Brand -Minus 2025", 'Baru', '0'], $rows);

        // Tidak ada sel teks yang diawali karakter formula tanpa petik; angka tetap angka biasa.
        foreach ($cells as $cell) {
            $this->assertFalse($cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true), "Sel berbahaya: {$cell}");
        }
        $this->assertContains('250000000', $cells);
    }
}
