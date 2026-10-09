<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Models\User;
use App\Notifications\Admin\AdminActivityNotification;
use App\Notifications\Admin\PurchaseRequestActivity;
use App\Notifications\Admin\ServiceBookingActivity;
use App\Notifications\Admin\TestDriveActivity;
use App\Notifications\TestDriveStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Notifikasi admin: test drive, pengajuan, booking servis baru / dibatalkan customer.
 */
class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $secondAdmin;

    private User $customer;

    private Car $car;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->admin()->create();
        $this->secondAdmin = User::factory()->admin()->create();
        $this->customer = User::factory()->create(['name' => 'Budi Santoso', 'phone' => '081211112222']);
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Kicks e-Power VL', 'year' => 2025, 'slug' => 'nissan-kicks-e-power-vl-2025',
            'vehicle_condition' => Car::CONDITION_NEW, 'price' => 520_000_000, 'stock' => 3, 'is_active' => true,
        ]);
        $this->service = Service::factory()->create(['name' => 'Servis Berkala']);
    }

    private function bookTestDrive(): void
    {
        $this->actingAs($this->customer)->post(route('test-drives.store'), [
            'car_id' => $this->car->id, 'preferred_date' => '2026-10-12', 'preferred_time' => '10:00', 'phone' => '081234567890',
        ])->assertSessionHasNoErrors();
    }

    // ---------- Pengiriman ----------

    public function test_booking_test_drive_baru_dikirim_ke_semua_admin(): void
    {
        $this->bookTestDrive();
        $testDrive = TestDrive::sole();

        foreach ([$this->admin, $this->secondAdmin] as $admin) {
            $notification = $admin->notifications()->sole();
            $this->assertSame(TestDriveActivity::class, $notification->type);
            $this->assertSame([
                'title' => 'Test Drive Baru · Budi Santoso',
                'message' => 'Nissan Kicks e-Power VL 2025 · 12 Okt 2026 pukul 10:00 WIB',
                'status' => 'pending',
                'admin_note' => null,
                'route' => 'admin.test-drives.show',
                'params' => [$testDrive->id],
            ], $notification->data);
        }

        $this->assertSame(0, $this->customer->notifications()->count(), 'Customer tidak menerima notifikasi admin.');
    }

    public function test_pengajuan_baru_dikirim_ke_admin(): void
    {
        $this->actingAs($this->customer)->post(route('purchase-requests.store', $this->car), [
            'payment_method' => 'cash', 'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung',
        ])->assertSessionHasNoErrors();

        $notification = $this->admin->notifications()->sole();
        $this->assertSame(PurchaseRequestActivity::class, $notification->type);
        $this->assertSame('Pengajuan Pembelian Baru · Budi Santoso', $notification->data['title']);
        $this->assertSame('Nissan Kicks e-Power VL 2025 · Cash · Rp 520.000.000', $notification->data['message']);
        $this->assertSame([PurchaseRequest::sole()->id], $notification->data['params']);
    }

    public function test_booking_servis_baru_dikirim_ke_admin(): void
    {
        $this->actingAs($this->customer)->post(route('service-bookings.store'), [
            'service_id' => $this->service->id, 'vehicle_model' => 'Nissan Livina VL', 'plate_number' => 'B 1234 ABC',
            'vehicle_year' => '2020', 'mileage' => '45.000', 'preferred_date' => '2026-10-12', 'preferred_time' => '09:00',
            'phone' => '081234567890',
        ])->assertSessionHasNoErrors();

        $notification = $this->admin->notifications()->sole();
        $this->assertSame(ServiceBookingActivity::class, $notification->type);
        $this->assertSame('Booking Servis Baru · Budi Santoso', $notification->data['title']);
        $this->assertSame('Servis Berkala · Nissan Livina VL (B 1234 ABC) · 12 Okt 2026 pukul 09:00 WIB', $notification->data['message']);
        $this->assertSame('admin.service-bookings.show', $notification->data['route']);
    }

    public function test_booking_gagal_validasi_tidak_mengirim_notifikasi(): void
    {
        $this->actingAs($this->customer)->post(route('test-drives.store'), ['car_id' => $this->car->id])
            ->assertSessionHasErrors();

        $this->assertSame(0, $this->admin->notifications()->count());
    }

    public function test_pembatalan_customer_dikirim_sekali(): void
    {
        $testDrive = TestDrive::factory()->recycle($this->customer)->recycle($this->car)->create();
        $purchase = PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->create();
        $booking = ServiceBooking::factory()->recycle($this->customer)->recycle($this->service)->create();

        foreach ([
            route('account.test-drives.cancel', $testDrive),
            route('account.purchase-requests.cancel', $purchase),
            route('account.service-bookings.cancel', $booking),
        ] as $url) {
            $this->actingAs($this->customer)->patch($url)->assertSessionHas('success');
            // Klik ganda: sudah batal, tidak ada notifikasi kedua.
            $this->actingAs($this->customer)->patch($url)->assertSessionHas('status');
        }

        $titles = $this->admin->notifications()->get()->pluck('data.title')->sort()->values()->all();
        $this->assertSame([
            'Booking Servis Dibatalkan Customer · Budi Santoso',
            'Pengajuan Pembelian Dibatalkan Customer · Budi Santoso',
            'Test Drive Dibatalkan Customer · Budi Santoso',
        ], $titles);
        $this->assertSame(['cancelled'], $this->admin->notifications()->get()->pluck('data.status')->unique()->values()->all());
    }

    public function test_perubahan_status_oleh_admin_tidak_membuat_notifikasi_admin(): void
    {
        $testDrive = TestDrive::factory()->recycle($this->customer)->recycle($this->car)->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.test-drives.update-status', $testDrive), ['status' => 'confirmed'])
            ->assertSessionHas('success');

        $this->assertSame(0, $this->admin->notifications()->count());
        $this->assertSame(TestDriveStatusChanged::class, $this->customer->notifications()->sole()->type);
    }

    // ---------- Halaman & lonceng ----------

    public function test_lonceng_topbar_dan_menu_sidebar_menampilkan_jumlah(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Notifikasi"', false)
            ->assertSee('href="'.route('admin.notifications.index').'"', false);

        $this->bookTestDrive();
        $this->actingAs($this->customer)->patch(route('account.test-drives.cancel', TestDrive::sole()));

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertSee('aria-label="Notifikasi, 2 belum dibaca"', false)
            ->assertSee('2<span class="visually-hidden"> belum dibaca</span>', false);
    }

    public function test_halaman_notifikasi_admin_dan_membuka_detail(): void
    {
        $this->actingAs($this->admin)->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Belum ada notifikasi');

        $this->bookTestDrive();
        $testDrive = TestDrive::sole();
        $notification = $this->admin->notifications()->sole();

        $this->actingAs($this->admin)->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('Test Drive Baru · Budi Santoso')
            ->assertSee('Tandai semua dibaca')
            ->assertSee('is-unread', false);

        $this->actingAs($this->admin)->get(route('admin.notifications.open', $notification->id))
            ->assertRedirect(route('admin.test-drives.show', $testDrive));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertNull($this->secondAdmin->notifications()->sole()->read_at, 'Admin lain tetap belum membaca.');
    }

    public function test_tandai_semua_dibaca_hanya_milik_sendiri(): void
    {
        $this->bookTestDrive();

        $this->actingAs($this->admin)->patch(route('admin.notifications.read-all'))
            ->assertRedirect(route('admin.notifications.index'))
            ->assertSessionHas('success', 'Semua notifikasi ditandai sudah dibaca.');

        $this->assertSame(0, $this->admin->unreadNotifications()->count());
        $this->assertSame(1, $this->secondAdmin->unreadNotifications()->count());
    }

    public function test_notifikasi_admin_lain_404(): void
    {
        $this->bookTestDrive();
        $other = $this->secondAdmin->notifications()->sole();

        $this->actingAs($this->admin)->get(route('admin.notifications.open', $other->id))->assertNotFound();
        $this->assertNull($other->fresh()->read_at);
    }

    public function test_customer_dan_tamu_tidak_bisa_membuka_halaman_admin(): void
    {
        $this->get(route('admin.notifications.index'))->assertRedirect(route('login'));
        $this->actingAs($this->customer)->get(route('admin.notifications.index'))->assertForbidden();
    }

    public function test_admin_yang_membuka_notifikasi_customer_diarahkan_ke_halaman_admin(): void
    {
        $this->actingAs($this->admin)->get(route('account.notifications.index'))
            ->assertRedirect(route('admin.notifications.index'));
    }

    public function test_notify_admins_tanpa_admin_tidak_error(): void
    {
        User::where('role', User::ROLE_ADMIN)->delete();
        $testDrive = TestDrive::factory()->recycle($this->customer)->recycle($this->car)->create();

        AdminActivityNotification::notifyAdmins(new TestDriveActivity($testDrive));

        $this->assertDatabaseCount('notifications', 0);
    }
}
