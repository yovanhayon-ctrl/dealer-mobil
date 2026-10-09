<?php

namespace Tests\Feature\Public;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Riwayat servis customer (/akun/servis) + pembatalan.
 */
class AccountServiceBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 10:00:00', 'Asia/Jakarta'));
        $this->customer = User::factory()->create();
        $this->service = Service::factory()->create(['name' => 'Servis Berkala']);
    }

    private function booking(array $attributes = [], ?User $user = null): ServiceBooking
    {
        return ServiceBooking::factory()->recycle($user ?? $this->customer)->recycle($this->service)->create([
            'vehicle_model' => 'Nissan Livina VL',
            'plate_number' => 'B 1234 ABC',
            'vehicle_year' => 2020,
            'mileage' => 45_000,
            'preferred_date' => '2026-10-12',
            'preferred_time' => '09:00',
            ...$attributes,
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $booking = $this->booking();

        $this->get(route('account.service-bookings.index'))->assertRedirect(route('login'));
        $this->patch(route('account.service-bookings.cancel', $booking))->assertRedirect(route('login'));

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_riwayat_kosong_menampilkan_empty_state(): void
    {
        $this->actingAs($this->customer)->get(route('account.service-bookings.index'))
            ->assertOk()
            ->assertSee('Belum ada booking servis');
    }

    public function test_riwayat_hanya_milik_sendiri_dan_menampilkan_detail(): void
    {
        $this->booking(['complaint' => 'AC kurang dingin.', 'status' => 'completed', 'admin_note' => 'Freon ditambah.']);
        $this->booking(['plate_number' => 'Z 9999 ZZ'], User::factory()->create());

        $this->actingAs($this->customer)->get(route('account.service-bookings.index'))
            ->assertOk()
            ->assertSeeInOrder(['Servis Berkala', 'Selesai', 'Nissan Livina VL (2020)', 'B 1234 ABC', '45.000 km', 'Senin, 12 Okt 2026', '09:00 WIB'])
            ->assertSee('AC kurang dingin.')
            ->assertSee('Freon ditambah.')
            ->assertDontSee('Z 9999 ZZ');
    }

    public function test_tombol_batalkan_hanya_untuk_pending(): void
    {
        $pending = $this->booking();
        $confirmed = $this->booking(['plate_number' => 'B 2 C', 'status' => 'confirmed']);

        $this->actingAs($this->customer)->get(route('account.service-bookings.index'))
            ->assertSee(route('account.service-bookings.cancel', $pending))
            ->assertDontSee(route('account.service-bookings.cancel', $confirmed));
    }

    public function test_batal_saat_pending(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->customer)
            ->from(route('account.service-bookings.index'))
            ->patch(route('account.service-bookings.cancel', $booking))
            ->assertRedirect(route('account.service-bookings.index'))
            ->assertSessionHas('success', 'Booking servis berhasil dibatalkan.');

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->admin_note);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notCancellable(): array
    {
        return [
            'dikonfirmasi' => ['confirmed'],
            'dikerjakan' => ['in_progress'],
            'selesai' => ['completed'],
        ];
    }

    #[DataProvider('notCancellable')]
    public function test_tidak_bisa_batal_selain_pending(string $status): void
    {
        $booking = $this->booking(['status' => $status]);

        $this->actingAs($this->customer)
            ->patch(route('account.service-bookings.cancel', $booking))
            ->assertSessionHas('error');

        $this->assertSame($status, $booking->fresh()->status);
    }

    public function test_klik_ganda_batal_aman(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->customer)->patch(route('account.service-bookings.cancel', $booking))->assertSessionHas('success');
        $this->actingAs($this->customer)->patch(route('account.service-bookings.cancel', $booking))
            ->assertSessionHas('status', 'Booking servis ini sudah dibatalkan.');
    }

    public function test_tidak_bisa_membatalkan_milik_orang_lain(): void
    {
        $booking = $this->booking([], User::factory()->create());

        $this->actingAs($this->customer)
            ->patch(route('account.service-bookings.cancel', $booking))
            ->assertNotFound();

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_dropdown_navbar_servis_saya_hanya_untuk_customer(): void
    {
        $this->actingAs($this->customer)->get(route('home'))
            ->assertSee('Servis Saya')
            ->assertSee(route('account.service-bookings.index'));

        $this->actingAs(User::factory()->admin()->create())->get(route('home'))
            ->assertDontSee('Servis Saya');
    }

    public function test_jumlah_query_tetap_walau_data_bertambah(): void
    {
        $this->booking();

        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->customer)->get(route('account.service-bookings.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $before = $count();
        ServiceBooking::factory()->count(4)->recycle($this->customer)->create();
        $this->assertSame($before, $count());
    }
}
