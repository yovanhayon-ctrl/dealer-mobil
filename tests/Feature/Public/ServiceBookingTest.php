<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\ServiceBookingController;
use App\Http\Requests\ServiceBookingRequest;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Halaman publik /servis dan booking servis customer (/servis/booking).
 */
class ServiceBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 10:00:00', 'Asia/Jakarta'));
        $this->customer = User::factory()->create(['name' => 'Budi Santoso', 'phone' => '081211112222']);
        $this->service = Service::factory()->create([
            'name' => 'Servis Berkala', 'slug' => 'servis-berkala', 'price_from' => 450_000, 'duration_minutes' => 120,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'service_id' => $this->service->id,
            'vehicle_model' => 'Nissan Livina VL',
            'plate_number' => 'B 1234 ABC',
            'vehicle_year' => '2020',
            'mileage' => '45.000',
            'preferred_date' => '2026-10-12',
            'preferred_time' => '09:00',
            'phone' => '081234567890',
            'complaint' => 'AC kurang dingin.',
            ...$overrides,
        ];
    }

    private function book(array $overrides = [], ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->customer)
            ->from(route('service-bookings.create'))
            ->post(route('service-bookings.store'), $this->payload($overrides));
    }

    private function bookingFor(array $attributes = [], ?User $user = null): ServiceBooking
    {
        return ServiceBooking::factory()->recycle($user ?? User::factory()->create())->recycle($this->service)->create([
            'plate_number' => 'D 5555 XY',
            'preferred_date' => '2026-10-12',
            'preferred_time' => '09:00',
            ...$attributes,
        ]);
    }

    // ---------- Halaman /servis ----------

    public function test_halaman_servis_publik_menampilkan_layanan_aktif(): void
    {
        Service::factory()->create(['name' => 'Suku Cadang', 'price_from' => null, 'duration_minutes' => null]);
        Service::factory()->inactive()->create(['name' => 'Layanan Lama']);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('<title>Servis &amp; Perawatan', false)
            ->assertSeeInOrder(['Servis Berkala', 'Rp 450.000', '±120 menit', 'Suku Cadang', 'hubungi dealer'])
            ->assertDontSee('Layanan Lama')
            ->assertSee('href="'.route('service-bookings.create', ['layanan' => 'servis-berkala']).'"', false)
            ->assertSee('08:00-15:00 WIB');
    }

    public function test_halaman_servis_kosong(): void
    {
        Service::query()->update(['is_active' => false]);

        $this->get(route('services.index'))->assertOk()->assertSee('Layanan belum tersedia');
    }

    public function test_menu_navbar_servis_dan_aktif_di_halaman_booking(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('href="'.route('services.index').'"', false);

        $this->actingAs($this->customer)->get(route('service-bookings.create'))
            ->assertSee('class="nav-link active" href="'.route('services.index').'"', false);
    }

    public function test_tombol_whatsapp_hanya_jika_nomor_diisi(): void
    {
        config(['dealer.whatsapp' => '']);
        $this->get(route('services.index'))->assertDontSee('Konsultasi Servis');

        config(['dealer.whatsapp' => '6281234567890']);
        $this->get(route('services.index'))->assertSee('https://wa.me/6281234567890?text=', false);
    }

    // ---------- Akses form ----------

    public function test_tamu_diarahkan_ke_login_lalu_kembali_ke_form(): void
    {
        $url = route('service-bookings.create', ['layanan' => 'servis-berkala']);

        $this->get($url)->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $this->customer->email, 'password' => 'password'])
            ->assertRedirect($url);
    }

    public function test_tamu_tidak_bisa_mengirim_booking(): void
    {
        $this->post(route('service-bookings.store'), $this->payload())->assertRedirect(route('login'));

        $this->assertSame(0, ServiceBooking::count());
    }

    public function test_admin_melihat_pesan_dan_tidak_bisa_booking(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('service-bookings.create'))
            ->assertOk()
            ->assertSee(ServiceBookingRequest::ADMIN_MESSAGE)
            ->assertDontSee('Kirim Booking');

        $this->book(user: $admin)
            ->assertRedirect(route('service-bookings.create'))
            ->assertSessionHas('error', ServiceBookingRequest::ADMIN_MESSAGE);
        $this->assertSame(0, ServiceBooking::count());
    }

    public function test_form_memilih_layanan_dari_query_string_dan_hanya_layanan_aktif(): void
    {
        $inactive = Service::factory()->inactive()->create(['name' => 'Layanan Lama', 'slug' => 'layanan-lama']);

        $this->actingAs($this->customer)->get(route('service-bookings.create', ['layanan' => 'servis-berkala']))
            ->assertOk()
            ->assertSee('<option value="'.$this->service->id.'" selected', false)
            ->assertSee('Servis Berkala (mulai Rp 450.000)')
            ->assertDontSee('Layanan Lama')
            ->assertSee('value="081211112222"', false)
            ->assertSee('min="2026-10-09"', false)
            ->assertSee('max="2026-11-07"', false);

        $this->get(route('service-bookings.create', ['layanan' => $inactive->slug]))
            ->assertOk()
            ->assertDontSee('selected>Layanan Lama', false);
    }

    public function test_form_tanpa_layanan_aktif_menampilkan_empty_state(): void
    {
        $this->service->update(['is_active' => false]);

        $this->actingAs($this->customer)->get(route('service-bookings.create'))
            ->assertOk()
            ->assertSee('Layanan servis belum tersedia')
            ->assertDontSee('Kirim Booking');
    }

    // ---------- Validasi ----------

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'layanan kosong' => [['service_id' => ''], 'service_id'],
            'layanan tidak ada' => [['service_id' => 999], 'service_id'],
            'model kosong' => [['vehicle_model' => '  '], 'vehicle_model'],
            'model terlalu panjang' => [['vehicle_model' => str_repeat('a', 101)], 'vehicle_model'],
            'plat kosong' => [['plate_number' => ''], 'plate_number'],
            'plat tidak valid' => [['plate_number' => '1234 ABC'], 'plate_number'],
            'plat angka terlalu panjang' => [['plate_number' => 'B 12345 A'], 'plate_number'],
            'tahun terlalu lama' => [['vehicle_year' => '1949'], 'vehicle_year'],
            'tahun masa depan' => [['vehicle_year' => '2028'], 'vehicle_year'],
            'km terlalu besar' => [['mileage' => '2.000.001'], 'mileage'],
            'tanggal hari ini' => [['preferred_date' => '2026-10-08'], 'preferred_date'],
            'tanggal lewat 30 hari' => [['preferred_date' => '2026-11-08'], 'preferred_date'],
            'tanggal format salah' => [['preferred_date' => '12/10/2026'], 'preferred_date'],
            'jam di luar slot' => [['preferred_time' => '16:00'], 'preferred_time'],
            'jam bukan slot penuh' => [['preferred_time' => '09:30'], 'preferred_time'],
            'nomor tidak valid' => [['phone' => '021555'], 'phone'],
            'keluhan terlalu panjang' => [['complaint' => str_repeat('a', 501)], 'complaint'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_validasi_menolak_isian_tidak_valid(array $overrides, string $field): void
    {
        $this->book($overrides)
            ->assertRedirect(route('service-bookings.create'))
            ->assertSessionHasErrors($field);

        $this->assertSame(0, ServiceBooking::count());
    }

    public function test_batas_rentang_dan_isian_opsional_kosong_diterima(): void
    {
        $this->book(['preferred_date' => '2026-10-09', 'preferred_time' => '08:00', 'vehicle_year' => '', 'mileage' => '', 'complaint' => '  '])
            ->assertSessionHasNoErrors();
        $this->book(['plate_number' => 'D 1 A', 'preferred_date' => '2026-11-07', 'preferred_time' => '15:00', 'vehicle_year' => '2027'])
            ->assertSessionHasNoErrors();

        $first = ServiceBooking::orderBy('id')->first();
        $this->assertNull($first->vehicle_year);
        $this->assertNull($first->mileage);
        $this->assertNull($first->complaint);
    }

    public function test_plat_dan_nomor_dinormalisasi(): void
    {
        $this->book(['plate_number' => ' b1234abc ', 'phone' => '+62 812-3456-7890'])->assertSessionHasNoErrors();

        $booking = ServiceBooking::sole();
        $this->assertSame('B 1234 ABC', $booking->plate_number);
        $this->assertSame('081234567890', $booking->phone);
        $this->assertSame(45_000, $booking->mileage);
    }

    public function test_layanan_nonaktif_ditolak(): void
    {
        $this->service->update(['is_active' => false]);

        $this->book()->assertSessionHasErrors(['service_id' => 'Layanan ini sedang tidak tersedia. Silakan pilih layanan lain.']);
    }

    // ---------- Aturan plat & kapasitas slot ----------

    /**
     * @return array<string, array{string}>
     */
    public static function activeStatuses(): array
    {
        return array_combine(ServiceBooking::ACTIVE_STATUSES, array_map(fn ($status) => [$status], ServiceBooking::ACTIVE_STATUSES));
    }

    #[DataProvider('activeStatuses')]
    public function test_plat_dengan_booking_aktif_milik_sendiri_ditolak_dengan_detail(string $status): void
    {
        $this->bookingFor(['plate_number' => 'B 1234 ABC', 'status' => $status, 'preferred_date' => '2026-10-15', 'preferred_time' => '10:00'], $this->customer);

        $label = ServiceBooking::STATUS_LABELS[$status];
        $this->book(['plate_number' => 'b 1234 abc'])
            ->assertSessionHasErrors(['plate_number' => "Kendaraan B 1234 ABC sudah punya booking servis aktif ({$label}, 15 Okt 2026 10:00 WIB). Lihat Riwayat Servis."]);
        $this->assertSame(1, ServiceBooking::count());
    }

    public function test_plat_dengan_booking_aktif_milik_orang_lain_ditolak_tanpa_detail(): void
    {
        $this->bookingFor(['plate_number' => 'B 1234 ABC', 'preferred_date' => '2026-10-15']);

        $this->book()->assertSessionHasErrors(['plate_number' => 'Kendaraan B 1234 ABC sudah punya booking servis aktif. Hubungi dealer jika ini kendaraan Anda.']);
    }

    public function test_plat_boleh_booking_lagi_setelah_status_akhir(): void
    {
        $this->bookingFor(['plate_number' => 'B 1234 ABC', 'status' => 'completed'], $this->customer);
        $this->bookingFor(['plate_number' => 'B 1234 ABC', 'status' => 'cancelled'], $this->customer);

        $this->book()->assertSessionHasNoErrors();
    }

    public function test_slot_penuh_ditolak_sesuai_kapasitas(): void
    {
        foreach (range(1, ServiceBooking::slotCapacity()) as $i) {
            $this->bookingFor(['plate_number' => "D {$i} XY"]);
        }

        $this->book()->assertSessionHasErrors(['preferred_time' => 'Slot bengkel pada jam ini sudah penuh, silakan pilih jam atau tanggal lain.']);

        // Jam lain dan tanggal lain masih boleh.
        $this->book(['preferred_time' => '10:00'])->assertSessionHasNoErrors();
        $this->book(['plate_number' => 'B 9 Z', 'preferred_date' => '2026-10-13'])->assertSessionHasNoErrors();
    }

    public function test_booking_selesai_atau_batal_tidak_memakai_slot(): void
    {
        $this->bookingFor(['plate_number' => 'D 1 XY', 'status' => 'cancelled']);
        $this->bookingFor(['plate_number' => 'D 2 XY', 'status' => 'completed']);
        $this->bookingFor(['plate_number' => 'D 3 XY']);

        config(['dealer.service_slot_capacity' => 2]);
        $this->book()->assertSessionHasNoErrors();

        config(['dealer.service_slot_capacity' => 1]);
        $this->book(['plate_number' => 'B 2 C'])->assertSessionHasErrors('preferred_time');
    }

    // ---------- Rate limit ----------

    public function test_rate_limit_booking_per_user(): void
    {
        foreach (range(1, ServiceBookingController::MAX_BOOKINGS_PER_MINUTE) as $i) {
            $this->book(['preferred_time' => '07:00'])->assertSessionHasErrors('preferred_time');
        }

        $this->book()
            ->assertRedirect(route('service-bookings.create'))
            ->assertSessionHas('error', 'Terlalu banyak percobaan booking servis. Coba lagi dalam 1 menit.');
        $this->assertSame(0, ServiceBooking::count());

        $this->book(['plate_number' => 'D 7 A'], User::factory()->create())->assertSessionHasNoErrors();

        $this->travel(61)->seconds();
        $this->book()->assertSessionHasNoErrors();
        $this->assertSame(2, ServiceBooking::count());
    }

    // ---------- Tersimpan ----------

    public function test_booking_tersimpan_pending_dan_diarahkan_ke_riwayat(): void
    {
        $this->book()
            ->assertRedirect(route('account.service-bookings.index'))
            ->assertSessionHas('success', 'Booking Servis Berkala untuk B 1234 ABC pada 12 Okt 2026 pukul 09:00 WIB berhasil dikirim. Tunggu konfirmasi dari dealer.');

        $booking = ServiceBooking::sole();
        $this->assertSame($this->customer->id, $booking->user_id);
        $this->assertSame($this->service->id, $booking->service_id);
        $this->assertSame('Nissan Livina VL', $booking->vehicle_model);
        $this->assertSame(2020, $booking->vehicle_year);
        $this->assertSame('2026-10-12', $booking->preferred_date->toDateString());
        $this->assertSame('09:00', $booking->timeLabel());
        $this->assertSame('AC kurang dingin.', $booking->complaint);
        $this->assertSame(ServiceBooking::STATUS_PENDING, $booking->status);
        $this->assertNull($booking->admin_note);
    }

    public function test_booking_baru_tampil_di_admin(): void
    {
        $this->book()->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.service-bookings.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('B 1234 ABC')
            ->assertSee('12 Okt 2026')
            ->assertSee('Menunggu');
    }
}
