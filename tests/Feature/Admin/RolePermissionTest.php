<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PaymentProof;
use App\Models\Promo;
use App\Models\PurchaseRequest;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Models\Testimonial;
use App\Models\User;
use App\Notifications\Admin\AdminActivityNotification;
use App\Notifications\Admin\PaymentProofActivity;
use App\Notifications\Admin\PurchaseRequestActivity;
use App\Notifications\Admin\ServiceBookingActivity;
use App\Notifications\MechanicJobAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Enam role staf (User::PERMISSIONS): menu, mode lihat saja, aksi terlarang, kelola staf, dan penerima notifikasi.
 */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, User> */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'owner' => User::factory()->owner(),
            'super_admin' => User::factory()->superAdmin(),
            'admin' => User::factory()->admin(),
            'sales' => User::factory()->sales(),
            'customer_service' => User::factory()->customerService(),
            'mechanic' => User::factory()->mechanic(),
            'customer' => User::factory(),
        ] as $role => $factory) {
            $this->users[$role] = $factory->create(['name' => 'User '.$role]);
        }
    }

    /**
     * Halaman GET => role yang boleh membukanya (selain itu 403).
     *
     * @return array<string, array{string, list<string>}>
     */
    public static function pages(): array
    {
        $all = ['owner', 'super_admin', 'admin', 'sales', 'customer_service'];
        $managers = ['owner', 'super_admin', 'admin'];

        return [
            'dashboard' => ['admin.dashboard', $all],
            'notifikasi' => ['admin.notifications.index', $all],
            'mobil' => ['admin.cars.index', [...$managers, 'sales', 'customer_service']],
            'tambah mobil' => ['admin.cars.create', $managers],
            'merek' => ['admin.brands.index', $managers],
            'kategori' => ['admin.categories.index', $managers],
            'promo' => ['admin.promos.index', [...$managers, 'sales', 'customer_service']],
            'tambah promo' => ['admin.promos.create', ['owner', 'admin']],
            'test drive' => ['admin.test-drives.index', $all],
            'pengajuan' => ['admin.purchase-requests.index', $all],
            'booking servis' => ['admin.service-bookings.index', [...$managers, 'customer_service']],
            'layanan servis' => ['admin.services.index', $managers],
            'ulasan' => ['admin.testimonials.index', [...$managers, 'customer_service']],
            'pengguna' => ['admin.users.index', [...$managers, 'customer_service']],
            'laporan' => ['admin.reports.index', $managers],
            'staf' => ['admin.staff.index', ['owner', 'super_admin']],
            'pengaturan' => ['admin.settings.edit', ['owner', 'super_admin']],
            'pekerjaan mekanik' => ['mechanic.jobs.index', ['mechanic']],
            'layanan mekanik' => ['mechanic.services.index', ['mechanic']],
            'notifikasi mekanik' => ['mechanic.notifications.index', ['mechanic']],
        ];
    }

    /**
     * @param  list<string>  $allowed
     */
    #[DataProvider('pages')]
    public function test_akses_halaman_per_role(string $route, array $allowed): void
    {
        foreach ($this->users as $role => $user) {
            $this->actingAs($user)->get(route($route))
                ->assertStatus(in_array($role, $allowed, true) ? 200 : 403, "{$role} membuka {$route}");
        }
    }

    public function test_sidebar_mengikuti_hak_akses(): void
    {
        $this->actingAs($this->users['sales'])->get(route('admin.dashboard'))
            ->assertSee('href="'.route('admin.cars.index').'"', false)
            ->assertSee('href="'.route('admin.purchase-requests.index').'"', false)
            ->assertDontSee('href="'.route('admin.service-bookings.index').'"', false)
            ->assertDontSee('href="'.route('admin.users.index').'"', false)
            ->assertDontSee('href="'.route('admin.reports.index').'"', false);

        $this->actingAs($this->users['customer_service'])->get(route('admin.dashboard'))
            ->assertSee('href="'.route('admin.service-bookings.index').'"', false)
            ->assertSee('href="'.route('admin.testimonials.index').'"', false)
            ->assertSee('href="'.route('admin.cars.index').'"', false)
            ->assertSee('href="'.route('admin.promos.index').'"', false)
            ->assertSee('href="'.route('admin.test-drives.index').'"', false)
            ->assertDontSee('href="'.route('admin.staff.index').'"', false);

        $this->actingAs($this->users['super_admin'])->get(route('admin.dashboard'))
            ->assertSee('href="'.route('admin.staff.index').'"', false)
            ->assertSee('href="'.route('admin.reports.index').'"', false);

        $this->actingAs($this->users['mechanic'])->get(route('mechanic.jobs.index'))
            ->assertSee('href="'.route('mechanic.services.index').'"', false)
            ->assertSee('href="'.route('mechanic.notifications.index').'"', false);
    }

    public function test_sales_hanya_melihat_mobil_dan_promo(): void
    {
        $car = Car::factory()->create(['name' => 'Magnite Premium']);
        $promo = Promo::factory()->create();
        $sales = $this->users['sales'];

        $this->actingAs($sales)->get(route('admin.cars.index'))
            ->assertOk()
            ->assertSee('Magnite Premium')
            ->assertDontSee(route('admin.cars.create'), false)
            ->assertDontSee(route('admin.cars.edit', $car), false)
            ->assertDontSee(route('admin.cars.toggle-active', $car), false)
            ->assertDontSee('data-delete-url', false);
        $this->get(route('admin.promos.index'))->assertOk()->assertDontSee(route('admin.promos.edit', $promo), false);

        $this->get(route('admin.cars.edit', $car))->assertForbidden();
        $this->put(route('admin.cars.update', $car), ['name' => 'X'])->assertForbidden();
        $this->patch(route('admin.cars.toggle-active', $car))->assertForbidden();
        $this->get(route('admin.cars.images.index', $car))->assertForbidden();
        $this->delete(route('admin.promos.destroy', $promo))->assertForbidden();
        $this->assertModelExists($promo);
    }

    public function test_hapus_data_master_hanya_owner_dan_super_admin(): void
    {
        foreach (['admin', 'sales', 'customer_service'] as $role) {
            $brand = Brand::factory()->create();
            $this->actingAs($this->users[$role])->delete(route('admin.brands.destroy', $brand))->assertForbidden();
            $this->assertModelExists($brand);
        }

        $brand = Brand::factory()->create();
        $this->actingAs($this->users['super_admin'])->get(route('admin.brands.index'))->assertSee('data-delete-url', false);
        $this->delete(route('admin.brands.destroy', $brand))->assertRedirect(route('admin.brands.index'));
        $this->assertModelMissing($brand);
    }

    public function test_pengajuan_sales_boleh_ubah_status_tanpa_urusan_pembayaran(): void
    {
        $purchase = PurchaseRequest::factory()->status('approved')->create();
        $proof = PaymentProof::factory()->recycle($purchase)->create(['purchase_request_id' => $purchase->id]);

        $this->actingAs($this->users['sales'])->get(route('admin.purchase-requests.show', $purchase))
            ->assertOk()
            ->assertSee(route('admin.purchase-requests.update-status', $purchase), false)
            ->assertDontSee(route('admin.payment-proofs.approve', [$purchase, $proof]), false)
            ->assertDontSee(route('admin.payment-proofs.file', [$purchase, $proof]), false)
            ->assertDontSee('Catat Pembayaran di Showroom');
        $this->patch(route('admin.payment-proofs.approve', [$purchase, $proof]))->assertForbidden();
        $this->get(route('admin.payment-proofs.file', [$purchase, $proof]))->assertForbidden();
        $this->post(route('admin.payment-refunds.store', $purchase), [])->assertForbidden();
        $this->assertTrue($proof->fresh()->isPending());

        $this->patch(route('admin.purchase-requests.update-status', $purchase), ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $purchase->fresh()->status);
    }

    public function test_customer_service_hanya_melihat_pengajuan_dan_ulasan(): void
    {
        $purchase = PurchaseRequest::factory()->status('pending')->create(['admin_note' => 'Menunggu dokumen.']);
        $testimonial = Testimonial::factory()->create();
        $cs = $this->users['customer_service'];

        $this->actingAs($cs)->get(route('admin.purchase-requests.show', $purchase))
            ->assertOk()
            ->assertDontSee(route('admin.purchase-requests.update-status', $purchase), false)
            ->assertSee('Menunggu dokumen.');
        $this->patch(route('admin.purchase-requests.update-status', $purchase), ['status' => 'processing'])->assertForbidden();
        $this->assertSame('pending', $purchase->fresh()->status);

        $this->get(route('admin.testimonials.index'))->assertOk()->assertDontSee(route('admin.testimonials.approve', $testimonial), false);
        $this->patch(route('admin.testimonials.approve', $testimonial))->assertForbidden();
    }

    public function test_super_admin_melihat_tanpa_keputusan_bisnis(): void
    {
        $super = $this->users['super_admin'];
        $purchase = PurchaseRequest::factory()->status('approved')->create();
        $proof = PaymentProof::factory()->recycle($purchase)->create(['purchase_request_id' => $purchase->id]);
        $promo = Promo::factory()->create();

        // Pengajuan: hanya lihat, tanpa ubah status, verifikasi bayar, file bukti, atau pengembalian dana.
        $this->actingAs($super)->get(route('admin.purchase-requests.show', $purchase))
            ->assertOk()
            ->assertDontSee(route('admin.purchase-requests.update-status', $purchase), false)
            ->assertDontSee(route('admin.payment-proofs.approve', [$purchase, $proof]), false);
        $this->patch(route('admin.purchase-requests.update-status', $purchase), ['status' => 'completed'])->assertForbidden();
        $this->patch(route('admin.payment-proofs.approve', [$purchase, $proof]))->assertForbidden();
        $this->get(route('admin.payment-proofs.file', [$purchase, $proof]))->assertForbidden();
        $this->post(route('admin.payment-refunds.store', $purchase), [])->assertForbidden();
        $this->assertSame('approved', $purchase->fresh()->status);
        $this->assertTrue($proof->fresh()->isPending());

        // Promo: hanya lihat, tanpa ubah & hapus.
        $this->get(route('admin.promos.index'))->assertOk()
            ->assertDontSee(route('admin.promos.edit', $promo), false)
            ->assertDontSee('data-delete-url="'.route('admin.promos.destroy', $promo).'"', false);
        $this->get(route('admin.promos.edit', $promo))->assertForbidden();
        $this->delete(route('admin.promos.destroy', $promo))->assertForbidden();
        $this->assertModelExists($promo);

        // Tugas sistem tetap: staf & pengaturan.
        $this->get(route('admin.staff.index'))->assertOk();
        $this->get(route('admin.settings.edit'))->assertOk();

        // Owner tetap memegang keputusan bisnis.
        $this->actingAs($this->users['owner'])->delete(route('admin.promos.destroy', $promo))->assertRedirect(route('admin.promos.index'));
        $this->assertModelMissing($promo);
    }

    public function test_customer_service_hanya_melihat_test_drive_mobil_dan_promo(): void
    {
        $testDrive = TestDrive::factory()->create(['status' => 'pending', 'admin_note' => 'Hubungi ulang sore.']);
        $car = Car::factory()->create();
        $promo = Promo::factory()->create();

        $this->actingAs($this->users['customer_service'])->get(route('admin.test-drives.show', $testDrive))
            ->assertOk()
            ->assertSee('Hubungi ulang sore.')
            ->assertSee('Status dan jadwal test drive diurus oleh Sales atau Admin Dealer.')
            ->assertDontSee(route('admin.test-drives.update-status', $testDrive), false)
            ->assertDontSee(route('admin.test-drives.reschedule', $testDrive), false);
        $this->patch(route('admin.test-drives.update-status', $testDrive), ['status' => 'confirmed'])->assertForbidden();
        $this->patch(route('admin.test-drives.reschedule', $testDrive), [
            'preferred_date' => today()->addDays(3)->toDateString(), 'preferred_time' => TestDrive::TIME_SLOTS[0],
        ])->assertForbidden();
        $this->assertSame('pending', $testDrive->fresh()->status);

        $this->get(route('admin.cars.index'))->assertOk()->assertDontSee(route('admin.cars.edit', $car), false);
        $this->get(route('admin.cars.edit', $car))->assertForbidden();
        $this->get(route('admin.promos.index'))->assertOk()->assertDontSee(route('admin.promos.edit', $promo), false);
        $this->get(route('admin.promos.create'))->assertForbidden();

        // Sales tetap mengurus test drive.
        $this->actingAs($this->users['sales'])->get(route('admin.test-drives.show', $testDrive))
            ->assertSee(route('admin.test-drives.update-status', $testDrive), false);
        $this->patch(route('admin.test-drives.update-status', $testDrive), ['status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $testDrive->fresh()->status);
    }

    public function test_customer_service_mengelola_booking_servis(): void
    {
        $booking = ServiceBooking::factory()->create();
        $mechanic = $this->users['mechanic'];

        $this->actingAs($this->users['customer_service'])
            ->patch(route('admin.service-bookings.update-status', $booking), ['status' => 'confirmed'])
            ->assertSessionHasNoErrors();
        $this->patch(route('admin.service-bookings.assign-mechanic', $booking), ['mechanic_id' => $mechanic->id])->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame($mechanic->id, $booking->mechanic_id);
    }

    public function test_customer_service_hanya_melihat_customer_di_pengguna(): void
    {
        $cs = $this->users['customer_service'];

        $this->actingAs($cs)->get(route('admin.users.index', ['role' => 'semua']))
            ->assertOk()
            ->assertSee('User customer')
            ->assertDontSee('User owner')
            ->assertDontSee('id="filter_role"', false);
        $this->get(route('admin.users.show', $this->users['customer']))->assertOk();
        $this->get(route('admin.users.show', $this->users['admin']))->assertNotFound();

        $this->actingAs($this->users['admin'])->get(route('admin.users.show', $this->users['sales']))->assertOk();
    }

    public function test_super_admin_mengelola_staf_kecuali_owner_dan_super_admin(): void
    {
        $super = $this->users['super_admin'];
        $otherSuper = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get(route('admin.staff.create'))
            ->assertOk()
            ->assertSee('value="sales"', false)
            ->assertDontSee('value="super_admin"', false);
        $this->get(route('admin.staff.edit', $this->users['sales']))->assertOk();
        $this->get(route('admin.staff.edit', $this->users['owner']))->assertNotFound();
        $this->get(route('admin.staff.edit', $otherSuper))->assertNotFound();
        $this->get(route('admin.staff.edit', $super))->assertNotFound();

        $this->post(route('admin.staff.store'), [
            'name' => 'Calon Super', 'email' => 'calon@dealer.test', 'role' => 'super_admin',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('role');
        $this->post(route('admin.staff.store'), [
            'name' => 'Sinta Sales', 'email' => 'sinta@dealer.test', 'role' => 'sales',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('admin.staff.index'));
        $this->assertDatabaseHas('users', ['email' => 'sinta@dealer.test', 'role' => 'sales']);
        $this->assertDatabaseMissing('users', ['email' => 'calon@dealer.test']);

        // Owner boleh membuat Super Admin.
        $this->actingAs($this->users['owner'])->post(route('admin.staff.store'), [
            'name' => 'Super Baru', 'email' => 'super@dealer.test', 'role' => 'super_admin',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('admin.staff.index'));
        $this->assertDatabaseHas('users', ['email' => 'super@dealer.test', 'role' => 'super_admin']);
    }

    public function test_notifikasi_admin_hanya_ke_role_yang_berhak(): void
    {
        AdminActivityNotification::notifyAdmins(new PurchaseRequestActivity(PurchaseRequest::factory()->create()));
        AdminActivityNotification::notifyAdmins(new ServiceBookingActivity(ServiceBooking::factory()->create()));
        $purchase = PurchaseRequest::factory()->status('approved')->create();
        AdminActivityNotification::notifyAdmins(new PaymentProofActivity(
            PaymentProof::factory()->recycle($purchase)->create(['purchase_request_id' => $purchase->id]), $purchase,
        ));

        $received = fn (string $role) => $this->users[$role]->notifications()->pluck('type')
            ->map(fn ($type) => class_basename($type))->sort()->values()->all();

        $this->assertSame(['PurchaseRequestActivity', 'ServiceBookingActivity'], $received('super_admin'));
        $this->assertSame(['PaymentProofActivity', 'PurchaseRequestActivity', 'ServiceBookingActivity'], $received('admin'));
        $this->assertSame(['PurchaseRequestActivity'], $received('sales'));
        $this->assertSame(['PurchaseRequestActivity', 'ServiceBookingActivity'], $received('customer_service'));
        $this->assertSame([], $received('mechanic'));
    }

    public function test_mekanik_diberi_tahu_pekerjaan_baru_dan_melihat_layanan(): void
    {
        $mechanic = $this->users['mechanic'];
        Service::factory()->create(['name' => 'Ban & Aki', 'checklist' => "Cek tekanan ban\nCek aki", 'is_active' => true]);
        $booking = ServiceBooking::factory()->create(['mechanic_id' => $mechanic->id]);

        // Masih menunggu: belum ada notifikasi; saat dikonfirmasi baru muncul di Pekerjaan Saya.
        $this->assertSame(0, $mechanic->notifications()->count());
        $this->actingAs($this->users['admin'])->patch(route('admin.service-bookings.update-status', $booking), ['status' => 'confirmed']);

        $notification = $mechanic->notifications()->sole();
        $this->assertSame(MechanicJobAssigned::class, $notification->type);
        $this->assertSame('Pekerjaan Servis Baru', $notification->data['title']);

        $this->actingAs($mechanic)->get(route('mechanic.notifications.index'))->assertOk()->assertSee('Pekerjaan Servis Baru');
        $this->get(route('mechanic.notifications.open', $notification->id))->assertRedirect(route('mechanic.jobs.show', $booking));
        $this->get(route('account.notifications.index'))->assertRedirect(route('mechanic.notifications.index'));
        $this->get(route('mechanic.services.index'))->assertOk()->assertSee('Ban &amp; Aki', false)->assertSee('Cek tekanan ban');

        // Dialihkan ke mekanik lain setelah dikonfirmasi: mekanik baru diberi tahu.
        $other = User::factory()->mechanic()->create();
        $this->actingAs($this->users['admin'])->patch(route('admin.service-bookings.assign-mechanic', $booking), ['mechanic_id' => $other->id]);
        $this->assertSame(1, $other->notifications()->count());
    }

    public function test_role_label_baru(): void
    {
        $this->assertSame('Admin Dealer', $this->users['admin']->roleLabel());
        $this->assertSame('Customer Service', $this->users['customer_service']->roleLabel());
        $this->actingAs($this->users['sales'])->get(route('admin.dashboard'))->assertSee('User sales (Sales)');
    }
}
