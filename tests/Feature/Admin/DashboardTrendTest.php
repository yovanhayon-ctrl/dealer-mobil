<?php

namespace Tests\Feature\Admin;

use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Models\User;
use App\Reports\DashboardTrend;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Grafik tren 6 bulan di dashboard admin.
 */
class DashboardTrendTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00:00'));
        $this->admin = User::factory()->admin()->create();
    }

    private function purchase(string $createdAt, string $status = PurchaseRequest::STATUS_PENDING, int $price = 100_000_000): PurchaseRequest
    {
        return PurchaseRequest::factory()->status($status)->create(['created_at' => $createdAt, 'car_price' => $price]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function monthsByKey(): array
    {
        return collect((new DashboardTrend)->months())->keyBy('key')->all();
    }

    public function test_rentang_enam_bulan_termasuk_bulan_kosong(): void
    {
        $months = (new DashboardTrend)->months();

        $this->assertSame(['2026-05', '2026-06', '2026-07', '2026-08', '2026-09', '2026-10'], array_column($months, 'key'));
        $this->assertSame(['Mei 2026', 'Jun 2026', 'Jul 2026', 'Agt 2026', 'Sep 2026', 'Okt 2026'], array_column($months, 'label'));

        foreach ($months as $month) {
            $this->assertSame(0, $month['purchases'] + $month['test_drives'] + $month['service_bookings'] + $month['units_sold'] + $month['sales_value']);
        }
    }

    public function test_angka_per_bulan_dan_batas_rentang(): void
    {
        // Di luar rentang: sebelum 1 Mei 2026 dan setelah Oktober.
        $this->purchase('2026-04-30 23:59:59', PurchaseRequest::STATUS_COMPLETED);
        $this->purchase('2026-11-01 00:00:00', PurchaseRequest::STATUS_COMPLETED);

        // Mei: 1 pengajuan pending (bukan terjual), batas awal rentang.
        $this->purchase('2026-05-01 00:00:00');
        // Agustus: approved + completed = terjual; rejected & cancelled tidak.
        $this->purchase('2026-08-03 09:00:00', PurchaseRequest::STATUS_APPROVED, 250_000_000);
        $this->purchase('2026-08-20 09:00:00', PurchaseRequest::STATUS_COMPLETED, 300_000_000);
        $this->purchase('2026-08-21 09:00:00', PurchaseRequest::STATUS_REJECTED, 999_000_000);
        $this->purchase('2026-08-22 09:00:00', PurchaseRequest::STATUS_CANCELLED, 999_000_000);

        TestDrive::factory()->count(2)->create(['created_at' => '2026-09-10 10:00:00']);
        TestDrive::factory()->create(['created_at' => '2026-10-31 23:59:59']);
        ServiceBooking::factory()->count(3)->create(['created_at' => '2026-06-05 08:00:00']);

        $months = $this->monthsByKey();

        $this->assertSame(1, $months['2026-05']['purchases']);
        $this->assertSame(0, $months['2026-05']['units_sold']);
        $this->assertSame(4, $months['2026-08']['purchases']);
        $this->assertSame(2, $months['2026-08']['units_sold']);
        $this->assertSame(550_000_000, $months['2026-08']['sales_value']);
        $this->assertSame(2, $months['2026-09']['test_drives']);
        $this->assertSame(1, $months['2026-10']['test_drives']);
        $this->assertSame(3, $months['2026-06']['service_bookings']);

        $this->assertSame(5, array_sum(array_column($months, 'purchases')), 'Data di luar rentang tidak ikut dihitung.');
    }

    public function test_jumlah_query_tetap_berapa_pun_datanya(): void
    {
        $count = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            (new DashboardTrend)->months();
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $empty = $count();
        $this->purchase('2026-07-01 10:00:00', PurchaseRequest::STATUS_APPROVED);
        $this->purchase('2026-09-01 10:00:00');
        TestDrive::factory()->count(3)->create(['created_at' => '2026-08-01 10:00:00']);

        $this->assertSame(3, $empty);
        $this->assertSame($empty, $count());
    }

    public function test_dashboard_menampilkan_grafik_dan_tabel_cadangan(): void
    {
        $car = Car::factory()->create();
        PurchaseRequest::factory()->recycle($car)->status(PurchaseRequest::STATUS_APPROVED)
            ->create(['created_at' => '2026-10-02 10:00:00', 'car_price' => 275_000_000]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

        $response->assertSee('Tren 6 Bulan Terakhir')
            ->assertSee('data-chart="activity"', false)
            ->assertSee('data-chart="sales"', false)
            ->assertSee('https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.js', false)
            ->assertSee('Rp 275.000.000');

        // Data di atribut sama dengan isi tabel.
        preg_match('/data-chart="sales" data-chart-data=\'([^\']+)\'/', $response->getContent(), $match);
        $sales = json_decode(html_entity_decode($match[1]), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['Mei 2026', 'Jun 2026', 'Jul 2026', 'Agt 2026', 'Sep 2026', 'Okt 2026'], $sales['labels']);
        $this->assertSame([0, 0, 0, 0, 0, 1], $sales['units']);
        $this->assertSame([0, 0, 0, 0, 0, 275_000_000], $sales['values']);
    }

    public function test_dashboard_tanpa_script_inline(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.dashboard'))->getContent();

        preg_match_all('/<script(?![^>]*\bsrc=)[^>]*>/i', $html, $inline);
        $this->assertSame([], $inline[0]);
    }

    public function test_grafik_tidak_dimuat_di_halaman_admin_lain(): void
    {
        $this->actingAs($this->admin)->get(route('admin.cars.index'))
            ->assertOk()
            ->assertDontSee('chart.umd.js', false);
    }

    public function test_customer_tidak_bisa_melihat(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    }
}
