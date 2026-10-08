<?php

namespace Tests\Feature\Admin;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ServiceBookingManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.test']);
        $this->service = Service::factory()->create(['name' => 'Servis Berkala']);
    }

    private function makeBooking(array $attributes = []): ServiceBooking
    {
        return ServiceBooking::factory()->recycle($this->customer)->recycle($this->service)->create([
            'vehicle_model' => 'Nissan Livina VL',
            'plate_number' => 'B 1234 ABC',
            'preferred_date' => '2026-10-10',
            'preferred_time' => '09:00',
            ...$attributes,
        ]);
    }

    private function changeStatus(ServiceBooking $booking, array $data)
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.service-bookings.show', $booking))
            ->patch(route('admin.service-bookings.update-status', $booking), $data);
    }

    /**
     * @return array<int, int>
     */
    private function listedIds(array $query = []): array
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.service-bookings.index', $query))
            ->assertOk()
            ->viewData('bookings')
            ->pluck('id')
            ->sort()
            ->values()
            ->all();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $booking = $this->makeBooking();

        $this->get('/admin/servis')->assertRedirect(route('login'));
        $this->get("/admin/servis/{$booking->id}")->assertRedirect(route('login'));
        $this->patch("/admin/servis/{$booking->id}/status", ['status' => 'confirmed'])->assertRedirect(route('login'));

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_customer_mendapat_403(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->customer);
        $this->get('/admin/servis')->assertForbidden();
        $this->get("/admin/servis/{$booking->id}")->assertForbidden();
        $this->patch("/admin/servis/{$booking->id}/status", ['status' => 'confirmed'])->assertForbidden();

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_admin_tidak_bisa_membuat_atau_menghapus(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->admin);
        $this->post('/admin/servis', [])->assertMethodNotAllowed();
        $this->delete("/admin/servis/{$booking->id}")->assertMethodNotAllowed();
        $this->put("/admin/servis/{$booking->id}", [])->assertMethodNotAllowed();

        $this->assertModelExists($booking);
    }

    public function test_daftar_menampilkan_data_dan_tanpa_n_plus_1(): void
    {
        $this->makeBooking(['status' => 'confirmed']);

        $this->actingAs($this->admin)->get(route('admin.service-bookings.index'))
            ->assertOk()
            ->assertSeeInOrder(['10 Okt 2026', '09:00 WIB', 'Budi Santoso', 'budi@example.test', 'Nissan Livina VL', 'B 1234 ABC', 'Servis Berkala', 'Dikonfirmasi']);

        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->admin)->get(route('admin.service-bookings.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $before = $count();
        ServiceBooking::factory()->count(5)->create();
        $this->assertSame($before, $count());
    }

    public function test_empty_state(): void
    {
        $this->actingAs($this->admin)->get(route('admin.service-bookings.index'))
            ->assertOk()->assertSee('Belum ada booking servis');
    }

    public function test_filter_kata_kunci_status_layanan_dan_tanggal(): void
    {
        $plate = $this->makeBooking(['plate_number' => 'B 9999 XYZ']);
        $other = ServiceBooking::factory()->create([
            'vehicle_model' => 'Nissan Juke', 'plate_number' => 'D 1 AA',
            'preferred_date' => '2026-10-20', 'status' => 'confirmed',
        ]);

        $this->assertSame([$plate->id], $this->listedIds(['q' => '9999']));
        $this->assertSame([$other->id], $this->listedIds(['q' => 'juke']));
        $this->assertSame([$plate->id], $this->listedIds(['q' => 'budi@example']));
        $this->assertSame([$other->id], $this->listedIds(['status' => 'confirmed']));
        $this->assertSame([$plate->id], $this->listedIds(['layanan' => $this->service->id]));
        $this->assertSame([$other->id], $this->listedIds(['dari' => '2026-10-15']));
        $this->assertSame([$plate->id], $this->listedIds(['sampai' => '2026-10-15']));

        // Nilai tidak dikenal diabaikan.
        $this->assertCount(2, $this->listedIds(['status' => 'aneh', 'layanan' => 'abc', 'dari' => '2026-13-01', 'urut' => 'x']));
    }

    public function test_detail_menampilkan_data_booking(): void
    {
        $booking = $this->makeBooking(['vehicle_year' => 2020, 'mileage' => 45_000, 'complaint' => 'AC kurang dingin.']);

        $this->actingAs($this->admin)->get(route('admin.service-bookings.show', $booking))
            ->assertOk()
            ->assertSee('Booking Servis #'.$booking->id)
            ->assertSee('Sabtu, 10 Oktober 2026')
            ->assertSee('Nissan Livina VL (2020)')
            ->assertSee('45.000 km')
            ->assertSee('AC kurang dingin.')
            ->assertSee('Tetap: Menunggu')
            ->assertSee('Dikonfirmasi')
            ->assertDontSee('<option value="in_progress"', false);
    }

    public function test_alur_status_lengkap(): void
    {
        $booking = $this->makeBooking(['preferred_date' => '2026-10-08']);

        $this->changeStatus($booking, ['status' => 'confirmed'])
            ->assertRedirect(route('admin.service-bookings.show', $booking))
            ->assertSessionHas('success', 'Status booking servis diubah dari Menunggu menjadi Dikonfirmasi.');

        $this->changeStatus($booking, ['status' => 'in_progress'])->assertSessionHasNoErrors();
        $this->changeStatus($booking, ['status' => 'completed', 'admin_note' => 'Oli & filter diganti.'])->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('completed', $booking->status);
        $this->assertSame('Oli & filter diganti.', $booking->admin_note);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidTransitions(): array
    {
        return [
            'menunggu → dikerjakan' => ['pending', 'in_progress'],
            'menunggu → selesai' => ['pending', 'completed'],
            'dikonfirmasi → selesai' => ['confirmed', 'completed'],
            'dikerjakan → batal' => ['in_progress', 'cancelled'],
            'selesai → menunggu' => ['completed', 'pending'],
            'batal → dikonfirmasi' => ['cancelled', 'confirmed'],
            'status tidak dikenal' => ['pending', 'approved'],
        ];
    }

    #[DataProvider('invalidTransitions')]
    public function test_transisi_tidak_valid_ditolak(string $from, string $to): void
    {
        $booking = $this->makeBooking(['status' => $from, 'preferred_date' => '2026-10-08']);

        $this->changeStatus($booking, ['status' => $to, 'admin_note' => 'Catatan percobaan.'])
            ->assertSessionHasErrors('status');

        $this->assertSame($from, $booking->fresh()->status);
    }

    public function test_batal_wajib_catatan_admin(): void
    {
        $booking = $this->makeBooking();

        $this->changeStatus($booking, ['status' => 'cancelled', 'admin_note' => ''])
            ->assertSessionHasErrors(['admin_note' => 'Catatan admin wajib diisi saat membatalkan booking servis (alasan untuk customer).']);
        $this->assertSame('pending', $booking->fresh()->status);

        $this->changeStatus($booking, ['status' => 'cancelled', 'admin_note' => 'Bengkel penuh, mohon pilih jadwal lain.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_dikerjakan_tidak_bisa_sebelum_tanggal_jadwal(): void
    {
        $booking = $this->makeBooking(['status' => 'confirmed', 'preferred_date' => '2026-10-10']);

        $this->actingAs($this->admin)->get(route('admin.service-bookings.show', $booking))
            ->assertSee('baru bisa dipilih pada tanggal jadwal servis');

        $this->changeStatus($booking, ['status' => 'in_progress'])
            ->assertSessionHasErrors(['status' => 'Servis belum bisa mulai dikerjakan sebelum tanggal jadwalnya (10 Okt 2026).']);
        $this->assertSame('confirmed', $booking->fresh()->status);

        $this->travelTo(Carbon::parse('2026-10-10 08:00:00', 'Asia/Jakarta'));
        $this->changeStatus($booking, ['status' => 'in_progress'])->assertSessionHasNoErrors();
        $this->assertSame('in_progress', $booking->fresh()->status);
    }

    public function test_hanya_catatan_yang_diubah_saat_status_kosong(): void
    {
        $booking = $this->makeBooking(['status' => 'completed']);

        $this->changeStatus($booking, ['status' => '', 'admin_note' => 'Servis berikutnya di 50.000 km.'])
            ->assertSessionHas('success', 'Catatan admin booking servis berhasil disimpan.');

        $booking->refresh();
        $this->assertSame('completed', $booking->status);
        $this->assertSame('Servis berikutnya di 50.000 km.', $booking->admin_note);
    }

    public function test_status_sama_dengan_sekarang_dianggap_tetap(): void
    {
        $booking = $this->makeBooking(['status' => 'confirmed']);

        $this->changeStatus($booking, ['status' => 'confirmed', 'admin_note' => 'Mohon datang tepat waktu.'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Catatan admin booking servis berhasil disimpan.');

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('Mohon datang tepat waktu.', $booking->admin_note);
    }
}
