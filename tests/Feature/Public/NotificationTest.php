<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Models\User;
use App\Notifications\PurchaseRequestStatusChanged;
use App\Notifications\ServiceBookingStatusChanged;
use App\Notifications\TestDriveStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

/**
 * Notifikasi perubahan status dari admin ke customer (lonceng navbar + halaman Notifikasi).
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create();
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Skyline GT-R R34',
            'year' => 1999,
            'stock' => 2,
        ]);
    }

    private function makeTestDrive(array $attributes = []): TestDrive
    {
        return TestDrive::factory()->recycle($this->customer)->recycle($this->car)->create([
            'preferred_date' => '2026-12-01',
            'preferred_time' => '10:00',
            ...$attributes,
        ]);
    }

    // ---------- Pengiriman ----------

    public function test_konfirmasi_test_drive_mengirim_notifikasi_ke_pemiliknya(): void
    {
        $testDrive = $this->makeTestDrive();

        $this->actingAs($this->admin)
            ->patch(route('admin.test-drives.update-status', $testDrive), ['status' => 'confirmed', 'admin_note' => 'Datang 15 menit lebih awal.'])
            ->assertSessionHas('success');

        $notification = $this->customer->notifications()->sole();
        $this->assertSame(TestDriveStatusChanged::class, $notification->type);
        $this->assertNull($notification->read_at);
        $this->assertSame([
            'title' => 'Test Drive Dikonfirmasi',
            'message' => 'Nissan Skyline GT-R R34 1999 · 01 Des 2026 pukul 10:00 WIB',
            'status' => 'confirmed',
            'admin_note' => 'Datang 15 menit lebih awal.',
            'route' => 'account.test-drives.index',
            'anchor' => "test-drive-{$testDrive->id}",
        ], $notification->data);

        $this->assertSame(0, $this->admin->notifications()->count());
    }

    public function test_pengajuan_disetujui_dan_ditolak_mengirim_notifikasi(): void
    {
        $approved = PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->status(PurchaseRequest::STATUS_PROCESSING)->create();
        $rejected = PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.purchase-requests.update-status', $approved), ['status' => 'approved'])
            ->assertSessionHas('success');
        $this->actingAs($this->admin)
            ->patch(route('admin.purchase-requests.update-status', $rejected), ['status' => 'rejected', 'admin_note' => 'Dokumen kredit belum lengkap.'])
            ->assertSessionHas('success');

        $notifications = $this->customer->notifications()->get()->keyBy(fn ($n) => $n->data['anchor']);
        $this->assertCount(2, $notifications);
        $this->assertSame(PurchaseRequestStatusChanged::class, $notifications["pengajuan-{$approved->id}"]->type);
        $this->assertSame('Pengajuan Pembelian Disetujui', $notifications["pengajuan-{$approved->id}"]->data['title']);
        $this->assertSame('Pengajuan Pembelian Ditolak', $notifications["pengajuan-{$rejected->id}"]->data['title']);
        $this->assertSame('Dokumen kredit belum lengkap.', $notifications["pengajuan-{$rejected->id}"]->data['admin_note']);
    }

    public function test_konfirmasi_booking_servis_mengirim_notifikasi(): void
    {
        $booking = ServiceBooking::factory()->recycle($this->customer)->create([
            'service_id' => Service::factory()->create(['name' => 'Servis Berkala'])->id,
            'vehicle_model' => 'Nissan Livina VL',
            'plate_number' => 'B 1234 JAF',
            'preferred_date' => '2026-12-02',
            'preferred_time' => '09:00',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.service-bookings.update-status', $booking), ['status' => 'confirmed'])
            ->assertSessionHas('success');

        $notification = $this->customer->notifications()->sole();
        $this->assertSame(ServiceBookingStatusChanged::class, $notification->type);
        $this->assertSame('Booking Servis Dikonfirmasi', $notification->data['title']);
        $this->assertSame('Servis Berkala · Nissan Livina VL (B 1234 JAF) · 02 Des 2026 pukul 09:00 WIB', $notification->data['message']);
        $this->assertSame("servis-{$booking->id}", $notification->data['anchor']);
    }

    public function test_tidak_ada_notifikasi_jika_status_tidak_berubah(): void
    {
        $testDrive = $this->makeTestDrive(['status' => 'confirmed']);

        // Hanya catatan (status tetap).
        $this->actingAs($this->admin)
            ->patch(route('admin.test-drives.update-status', $testDrive), ['status' => '', 'admin_note' => 'Catatan saja.'])
            ->assertSessionHas('success');

        // Status sudah terpasang (admin lain lebih dulu).
        $this->actingAs($this->admin)
            ->patch(route('admin.test-drives.update-status', $testDrive), ['status' => 'confirmed'])
            ->assertSessionMissing('error');

        // Transisi tidak valid.
        $this->actingAs($this->admin)
            ->patch(route('admin.test-drives.update-status', $testDrive), ['status' => 'pending']);

        $this->assertSame(0, $this->customer->notifications()->count());
    }

    public function test_pembatalan_oleh_customer_sendiri_tidak_membuat_notifikasi(): void
    {
        $testDrive = $this->makeTestDrive();

        $this->actingAs($this->customer)
            ->patch(route('account.test-drives.cancel', $testDrive))
            ->assertSessionHas('success');

        $this->assertSame(0, $this->customer->notifications()->count());
    }

    // ---------- Lonceng navbar ----------

    public function test_lonceng_menampilkan_jumlah_belum_dibaca(): void
    {
        $this->actingAs($this->customer)->get(route('home'))
            ->assertSee('aria-label="Notifikasi"', false)
            ->assertDontSee('bi-bell-fill', false);

        $this->customer->notify(new TestDriveStatusChanged($this->makeTestDrive(['status' => 'confirmed'])));
        $this->customer->notify(new TestDriveStatusChanged($this->makeTestDrive(['status' => 'cancelled', 'admin_note' => 'Unit dipakai pameran.'])));

        $this->actingAs($this->customer)->get(route('home'))
            ->assertSee('aria-label="Notifikasi, 2 belum dibaca"', false)
            ->assertSee('bi-bell-fill', false);
    }

    public function test_admin_dan_tamu_tidak_melihat_lonceng(): void
    {
        $this->get(route('home'))->assertDontSee('notification-bell', false);
        $this->actingAs($this->admin)->get(route('home'))->assertDontSee('notification-bell', false);
    }

    // ---------- Halaman notifikasi ----------

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('account.notifications.index'))->assertRedirect(route('login'));
    }

    public function test_halaman_menampilkan_notifikasi_milik_sendiri_saja(): void
    {
        $other = User::factory()->create();
        $other->notify(new TestDriveStatusChanged(TestDrive::factory()->recycle($other)->create(['status' => 'confirmed'])));

        $this->actingAs($this->customer)->get(route('account.notifications.index'))
            ->assertOk()
            ->assertSee('Belum ada notifikasi')
            ->assertDontSee('Tandai semua dibaca');

        $this->customer->notify(new TestDriveStatusChanged($this->makeTestDrive(['status' => 'cancelled', 'admin_note' => 'Unit dipakai pameran.'])));

        $this->actingAs($this->customer)->get(route('account.notifications.index'))
            ->assertOk()
            ->assertSee('Test Drive Dibatalkan')
            ->assertSee('Nissan Skyline GT-R R34 1999')
            ->assertSee('Unit dipakai pameran.')
            ->assertSee('Tandai semua dibaca')
            ->assertSee('is-unread', false);

        $this->assertSame(1, $this->customer->notifications()->count());
    }

    public function test_membuka_notifikasi_menandai_terbaca_dan_menuju_kartu_riwayat(): void
    {
        $testDrive = $this->makeTestDrive(['status' => 'confirmed']);
        $this->customer->notify(new TestDriveStatusChanged($testDrive));
        $notification = $this->customer->notifications()->sole();

        $this->actingAs($this->customer)
            ->get(route('account.notifications.open', $notification->id))
            ->assertRedirect(route('account.test-drives.index')."#test-drive-{$testDrive->id}");

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($this->customer)->get(route('account.test-drives.index'))
            ->assertSee('id="test-drive-'.$testDrive->id.'"', false);
    }

    public function test_notifikasi_milik_user_lain_404(): void
    {
        $other = User::factory()->create();
        $other->notify(new TestDriveStatusChanged(TestDrive::factory()->recycle($other)->create(['status' => 'confirmed'])));
        $notification = $other->notifications()->sole();

        $this->actingAs($this->customer)
            ->get(route('account.notifications.open', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_tautan_rusak_kembali_ke_halaman_notifikasi(): void
    {
        $notification = DatabaseNotification::create([
            'id' => fake()->uuid(),
            'type' => TestDriveStatusChanged::class,
            'notifiable_type' => $this->customer->getMorphClass(),
            'notifiable_id' => $this->customer->id,
            'data' => ['title' => 'Lama', 'route' => 'route.tidak.ada'],
        ]);

        $this->actingAs($this->customer)
            ->get(route('account.notifications.open', $notification->id))
            ->assertRedirect(route('account.notifications.index'));
    }

    public function test_tandai_semua_dibaca_hanya_milik_sendiri(): void
    {
        $other = User::factory()->create();
        $other->notify(new TestDriveStatusChanged(TestDrive::factory()->recycle($other)->create(['status' => 'confirmed'])));
        $this->customer->notify(new TestDriveStatusChanged($this->makeTestDrive(['status' => 'confirmed'])));
        $this->customer->notify(new TestDriveStatusChanged($this->makeTestDrive(['status' => 'confirmed'])));

        $this->actingAs($this->customer)
            ->patch(route('account.notifications.read-all'))
            ->assertRedirect(route('account.notifications.index'))
            ->assertSessionHas('success', 'Semua notifikasi ditandai sudah dibaca.');

        $this->assertSame(0, $this->customer->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
    }
}
