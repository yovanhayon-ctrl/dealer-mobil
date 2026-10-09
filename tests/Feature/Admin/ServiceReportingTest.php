<?php

namespace Tests\Feature\Admin;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * JAF Service tahap S4: dashboard, detail/daftar pengguna, laporan & CSV.
 */
class ServiceReportingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-20 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create(['name' => 'Budi Santoso']);
    }

    private function booking(Service $service, string $status, string $date, array $attributes = []): ServiceBooking
    {
        return ServiceBooking::factory()->recycle($this->customer)->recycle($service)->create([
            'status' => $status,
            'preferred_date' => $date,
            'preferred_time' => '09:00',
            ...$attributes,
        ]);
    }

    /**
     * @return array<int, array<int, ?string>>
     */
    private function csvRows(TestResponse $response): array
    {
        $content = substr($response->streamedContent(), 3);

        return array_map(fn ($line) => str_getcsv($line, ';', '"', ''), preg_split('/\r?\n/', trim($content)));
    }

    // ---------- Dashboard ----------

    public function test_dashboard_menampilkan_booking_servis_terdekat_yang_aktif(): void
    {
        $service = Service::factory()->create(['name' => 'Servis Berkala']);
        $past = $this->booking($service, 'confirmed', '2026-09-19', ['plate_number' => 'B 1 LALU']);
        $cancelled = $this->booking($service, 'cancelled', '2026-09-22', ['plate_number' => 'B 2 BTL']);
        $completed = $this->booking($service, 'completed', '2026-09-20', ['plate_number' => 'B 3 SLS']);
        $today = $this->booking($service, 'in_progress', '2026-09-20', ['plate_number' => 'B 4 KRJ', 'vehicle_model' => 'Nissan Serena']);
        $later = $this->booking($service, 'pending', '2026-09-25', ['plate_number' => 'B 5 NTI']);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('upcomingServiceBookings', fn ($items) => $items->pluck('id')->all() === [$today->id, $later->id])
            ->assertSeeInOrder(['Booking Servis Terdekat', '20 Sep 2026', 'Budi Santoso', 'Nissan Serena', 'B 4 KRJ', 'Servis Berkala', 'Dikerjakan', 'B 5 NTI', 'Menunggu'])
            ->assertDontSee('B 1 LALU')
            ->assertDontSee('B 2 BTL')
            ->assertDontSee('B 3 SLS');

        $this->assertModelExists($past);
        $this->assertModelExists($cancelled);
        $this->assertModelExists($completed);
    }

    // ---------- Pengguna ----------

    public function test_daftar_pengguna_menampilkan_jumlah_servis(): void
    {
        $service = Service::factory()->create();
        $this->booking($service, 'pending', '2026-09-22');
        $this->booking($service, 'completed', '2026-09-10', ['plate_number' => 'B 9 X']);

        $this->actingAs($this->admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeInOrder(['Test Drive', 'Pengajuan', 'Servis'])
            ->assertViewHas('users', fn ($users) => $users->firstWhere('id', $this->customer->id)->service_bookings_count === 2);
    }

    public function test_detail_pengguna_menampilkan_riwayat_servis(): void
    {
        $service = Service::factory()->create(['name' => 'Detailing']);
        $booking = $this->booking($service, 'confirmed', '2026-09-22', ['vehicle_model' => 'Nissan Juke', 'plate_number' => 'B 2345 SRW']);

        $this->actingAs($this->admin)->get(route('admin.users.show', $this->customer))
            ->assertOk()
            ->assertSeeInOrder(['Booking servis', '1'])
            ->assertSeeInOrder(['Riwayat Servis', '22 Sep 2026', '09:00 WIB', 'Detailing', 'Nissan Juke', 'B 2345 SRW', 'Dikonfirmasi'])
            ->assertSee('href="'.route('admin.service-bookings.show', $booking).'"', false);

        $this->actingAs($this->admin)->get(route('admin.users.show', User::factory()->create()))
            ->assertOk()
            ->assertSee('Pengguna ini belum pernah booking servis.');
    }

    // ---------- Laporan ----------

    private function seedSeptember(): void
    {
        $berkala = Service::factory()->create(['name' => 'Servis Berkala']);
        $detailing = Service::factory()->create(['name' => 'Detailing']);

        $this->booking($berkala, 'completed', '2026-09-05', ['plate_number' => 'B 1 A']);
        $this->booking($berkala, 'completed', '2026-09-06', ['plate_number' => 'B 2 A']);
        $this->booking($berkala, 'confirmed', '2026-09-25', ['plate_number' => 'B 3 A']);
        $this->booking($detailing, 'cancelled', '2026-09-12', ['plate_number' => 'B 4 A']);
        // Di luar periode (Agustus): tidak dihitung bulan ini.
        $this->booking($detailing, 'completed', '2026-08-30', ['plate_number' => 'B 5 A']);
    }

    public function test_laporan_menampilkan_booking_servis_per_status_dan_layanan_terpopuler(): void
    {
        $this->seedSeptember();

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('test drive & servis menurut tanggal jadwal', false)
            ->assertSeeInOrder(['Booking Servis per Status', 'Selesai: 50,0%', 'Menunggu', '0', 'Dikonfirmasi', '1', 'Dikerjakan', '0', 'Selesai', '2', 'Dibatalkan', '1', 'Total', '4'])
            ->assertSeeInOrder(['Layanan Servis Terpopuler', 'Servis Berkala', '3', '2', 'Detailing', '1', '0']);
    }

    public function test_laporan_servis_kosong(): void
    {
        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Tidak ada booking servis terjadwal pada periode ini.');
    }

    public function test_export_csv_berisi_bagian_servis(): void
    {
        $this->seedSeptember();

        $rows = $this->csvRows($this->actingAs($this->admin)->get(route('admin.reports.export')));

        $this->assertContains(['Booking Servis per Status'], $rows);
        $this->assertContains(['Dikerjakan', '0'], $rows);
        $this->assertContains(['Total booking servis', '4'], $rows);
        $this->assertContains(['Persentase servis selesai (%)', '50,0'], $rows);
        $this->assertContains(['Servis Berkala', '3', '2'], $rows);
        $this->assertContains(['Detailing', '1', '0'], $rows);

        $august = $this->csvRows($this->actingAs($this->admin)->get(route('admin.reports.export', ['periode' => 'bulan-lalu'])));
        $this->assertContains(['Total booking servis', '1'], $august);
    }

    public function test_jumlah_query_laporan_tetap_walau_data_servis_bertambah(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $this->booking(Service::factory()->create(), 'completed', '2026-09-05');
        $before = $count();

        foreach (range(1, 6) as $i) {
            $this->booking(Service::factory()->create(), 'pending', '2026-09-1'.$i, ['plate_number' => "B {$i} Q"]);
        }

        $this->assertSame($before, $count());
    }
}
