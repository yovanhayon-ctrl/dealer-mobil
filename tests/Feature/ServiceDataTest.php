<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\ServiceBookingSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * JAF Service tahap S1: tabel, model, relasi, aturan status, dan seeder.
 */
class ServiceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_relasi_user_layanan_dan_booking(): void
    {
        $booking = ServiceBooking::factory()->create();

        $this->assertTrue($booking->user->serviceBookings->contains($booking));
        $this->assertTrue($booking->service->bookings->contains($booking));
    }

    public function test_layanan_yang_punya_booking_tidak_bisa_dihapus(): void
    {
        $booking = ServiceBooking::factory()->create();

        $this->expectException(QueryException::class);

        $booking->service->delete();
    }

    public function test_booking_ikut_terhapus_saat_user_dihapus(): void
    {
        $booking = ServiceBooking::factory()->create();

        $booking->user->delete();

        $this->assertModelMissing($booking);
        $this->assertModelExists($booking->service);
    }

    public function test_scope_active(): void
    {
        Service::factory()->create();
        Service::factory()->inactive()->create();
        $this->assertSame(1, Service::active()->count());

        foreach (ServiceBooking::STATUSES as $status) {
            ServiceBooking::factory()->status($status)->create();
        }
        $this->assertEqualsCanonicalizing(
            ServiceBooking::ACTIVE_STATUSES,
            ServiceBooking::active()->pluck('status')->all(),
        );
    }

    public function test_transisi_status_dan_pembatalan_customer(): void
    {
        $booking = ServiceBooking::factory()->make(['status' => ServiceBooking::STATUS_PENDING]);
        $this->assertTrue($booking->canTransitionTo(ServiceBooking::STATUS_CONFIRMED));
        $this->assertFalse($booking->canTransitionTo(ServiceBooking::STATUS_IN_PROGRESS));
        $this->assertTrue($booking->canBeCancelledByCustomer());

        $booking->status = ServiceBooking::STATUS_CONFIRMED;
        $this->assertTrue($booking->canTransitionTo(ServiceBooking::STATUS_IN_PROGRESS));
        $this->assertTrue($booking->canTransitionTo(ServiceBooking::STATUS_CANCELLED));
        $this->assertFalse($booking->canBeCancelledByCustomer());

        $booking->status = ServiceBooking::STATUS_IN_PROGRESS;
        $this->assertSame([ServiceBooking::STATUS_COMPLETED], $booking->allowedTransitions());

        foreach ([ServiceBooking::STATUS_COMPLETED, ServiceBooking::STATUS_CANCELLED] as $final) {
            $booking->status = $final;
            $this->assertTrue($booking->isFinal());
        }

        $this->assertSame('Dikerjakan', ServiceBooking::STATUS_LABELS[ServiceBooking::STATUS_IN_PROGRESS]);
    }

    public function test_normalisasi_plat_dan_kapasitas_slot(): void
    {
        $this->assertSame('B 1234 ABC', ServiceBooking::normalizePlate("  b  1234\tabc "));

        $this->assertSame(3, ServiceBooking::slotCapacity());
        config(['dealer.service_slot_capacity' => 0]);
        $this->assertSame(1, ServiceBooking::slotCapacity());
    }

    public function test_seeder_layanan_aman_dijalankan_ulang(): void
    {
        $this->seed(ServiceSeeder::class);
        Service::where('slug', 'detailing')->update(['price_from' => 999_000]);
        $this->seed(ServiceSeeder::class);

        $this->assertSame(6, Service::count());
        $this->assertEqualsCanonicalizing(
            ['servis-berkala', 'inspeksi-kendaraan', 'perawatan-ringan', 'ban-aki', 'detailing', 'suku-cadang'],
            Service::pluck('slug')->all(),
        );
        // Perubahan dari admin tidak ditimpa seeder.
        $this->assertSame(999_000, Service::where('slug', 'detailing')->value('price_from'));
        $this->assertNull(Service::where('slug', 'suku-cadang')->value('price_from'));
    }

    public function test_seeder_booking_servis_aman_dijalankan_ulang_dengan_variasi_status(): void
    {
        config(['dealer.seed.customer_password' => 'rahasia-lokal']);
        $this->seed([CustomerSeeder::class, ServiceSeeder::class]);

        $this->seed(ServiceBookingSeeder::class);
        $this->seed(ServiceBookingSeeder::class);

        $seeded = ServiceBooking::with('user:id,email')->get();
        $this->assertCount(8, $seeded);
        $this->assertEqualsCanonicalizing(ServiceBooking::STATUSES, $seeded->pluck('status')->unique()->values()->all());
        $this->assertTrue($seeded->every(fn (ServiceBooking $booking) => str_ends_with($booking->user->email, '@example.test')));

        // Selesai di masa lalu, dikerjakan hari ini.
        $this->assertTrue($seeded->where('status', 'completed')->every(fn (ServiceBooking $booking) => $booking->preferred_date->isPast()));
        $this->assertTrue($seeded->where('status', 'in_progress')->every(fn (ServiceBooking $booking) => $booking->preferred_date->isToday()));

        // Tidak ada slot yang melebihi kapasitas bengkel.
        $perSlot = ServiceBooking::active()
            ->get()
            ->countBy(fn (ServiceBooking $booking) => $booking->preferred_date->toDateString().' '.$booking->timeLabel());
        $this->assertLessThanOrEqual(ServiceBooking::slotCapacity(), $perSlot->max());
    }

    public function test_seeder_booking_servis_dilewati_tanpa_customer_dummy(): void
    {
        $this->seed(ServiceSeeder::class);
        $this->seed(ServiceBookingSeeder::class);

        $this->assertSame(0, ServiceBooking::count());
    }

    public function test_seeder_booking_servis_tidak_berjalan_di_production(): void
    {
        config(['dealer.seed.customer_password' => 'rahasia-lokal']);
        $this->seed([CustomerSeeder::class, ServiceSeeder::class]);
        $this->app['env'] = 'production';

        $this->app->make(ServiceBookingSeeder::class)->run();

        $this->assertSame(0, ServiceBooking::count());
    }

    public function test_customer_dummy_memakai_role_customer(): void
    {
        config(['dealer.seed.customer_password' => 'rahasia-lokal']);
        $this->seed([CustomerSeeder::class, ServiceSeeder::class, ServiceBookingSeeder::class]);

        $this->assertTrue(ServiceBooking::with('user')->get()->every(fn (ServiceBooking $booking) => $booking->user->role === User::ROLE_CUSTOMER));
    }
}
