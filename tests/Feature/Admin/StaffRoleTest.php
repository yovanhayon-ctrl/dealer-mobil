<?php

namespace Tests\Feature\Admin;

use App\Http\Requests\TestDriveRequest;
use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\Promo;
use App\Models\Service;
use App\Models\TestDrive;
use App\Models\User;
use App\Notifications\Admin\AdminActivityNotification;
use App\Notifications\Admin\TestDriveActivity;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Role staf: owner (kelola staf), admin, mekanik (panel Pekerjaan Saya), dan akun nonaktif.
 */
class StaffRoleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private User $mechanic;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->owner()->create(['name' => 'Pak Hendra', 'email' => 'owner@dealer.test']);
        $this->admin = User::factory()->admin()->create(['name' => 'Rina Admin']);
        $this->mechanic = User::factory()->mechanic()->create(['name' => 'Joko Mekanik', 'email' => 'joko@dealer.test']);
        $this->customer = User::factory()->create();
    }

    /**
     * @return array<string, array{string, int, int, int, int}>
     */
    public static function access(): array
    {
        // route => status untuk owner, admin, mekanik, customer
        return [
            'dashboard admin' => ['admin.dashboard', 200, 200, 403, 403],
            'kelola staf' => ['admin.staff.index', 200, 403, 403, 403],
            'tambah staf' => ['admin.staff.create', 200, 403, 403, 403],
            'pekerjaan mekanik' => ['mechanic.jobs.index', 403, 403, 200, 403],
        ];
    }

    #[DataProvider('access')]
    public function test_hak_akses_per_role(string $route, int $owner, int $admin, int $mechanic, int $customer): void
    {
        foreach (['owner' => $owner, 'admin' => $admin, 'mechanic' => $mechanic, 'customer' => $customer] as $role => $status) {
            $this->actingAs($this->{$role})->get(route($route))->assertStatus($status);
        }

        auth()->logout();
        $this->get(route($route))->assertRedirect(route('login'));
    }

    public function test_admin_tidak_bisa_menghapus_data_master_dan_membuka_laporan(): void
    {
        $car = Car::factory()->create();
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $promo = Promo::factory()->create();
        $service = Service::factory()->create();

        $this->actingAs($this->admin);
        foreach ([
            route('admin.cars.destroy', $car), route('admin.brands.destroy', $brand), route('admin.categories.destroy', $category),
            route('admin.promos.destroy', $promo), route('admin.services.destroy', $service),
        ] as $url) {
            $this->delete($url)->assertForbidden();
        }
        foreach ([$car, $brand, $category, $promo, $service] as $model) {
            $this->assertModelExists($model);
        }

        foreach (['admin.reports.index', 'admin.reports.export', 'admin.reports.export-excel'] as $route) {
            $this->get(route($route))->assertForbidden();
        }

        // Admin tetap bisa membuka daftar & form ubah, tanpa tombol hapus.
        $this->get(route('admin.services.index'))->assertOk()->assertDontSee('data-delete-url', false);
        $this->get(route('admin.services.edit', $service))->assertOk();
        $this->get(route('admin.dashboard'))->assertDontSee(route('admin.reports.index'), false);

        // Owner melihat tombol hapus.
        $this->actingAs($this->owner)->get(route('admin.services.index'))->assertSee('data-delete-url', false);
    }

    public function test_login_diarahkan_sesuai_role(): void
    {
        $this->assertLoginRedirect($this->owner, route('admin.dashboard'));
        $this->assertLoginRedirect($this->admin, route('admin.dashboard'));
        $this->assertLoginRedirect($this->mechanic, route('mechanic.jobs.index'));

        // Mekanik yang semula membuka halaman admin tetap ke panelnya.
        $this->get(route('admin.cars.index'))->assertRedirect(route('login'));
        $this->post(route('login.store'), ['email' => $this->mechanic->email, 'password' => 'password'])
            ->assertRedirect(route('mechanic.jobs.index'));
    }

    private function assertLoginRedirect(User $user, string $expected): void
    {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect($expected);
        $this->get(route('login'))->assertRedirect($expected);
        $this->post(route('logout'));
    }

    public function test_menu_panel_sesuai_role(): void
    {
        $this->actingAs($this->owner)->get(route('admin.dashboard'))
            ->assertSee(route('admin.staff.index'), false)
            ->assertSee('Pak Hendra (Owner)');

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertDontSee(route('admin.staff.index'), false);

        $this->actingAs($this->mechanic)->get(route('mechanic.jobs.index'))
            ->assertSee('Pekerjaan Saya')
            ->assertSee('Belum ada servis yang ditugaskan')
            ->assertDontSee(route('admin.dashboard'), false)
            ->assertDontSee(route('admin.notifications.index'), false);
    }

    public function test_owner_menambah_mekanik_yang_bisa_login(): void
    {
        $this->actingAs($this->owner)->post(route('admin.staff.store'), [
            'name' => 'Budi Mekanik', 'email' => 'Budi@Dealer.test', 'phone' => '0812 3456 7890', 'role' => 'mechanic',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('admin.staff.index'))
            ->assertSessionHas('success', 'Akun Mekanik Budi Mekanik dibuat. Berikan email dan kata sandinya untuk login.');

        $staff = User::where('email', 'budi@dealer.test')->sole();
        $this->assertTrue($staff->isMechanic());
        $this->assertTrue($staff->isActive());
        $this->assertSame('081234567890', $staff->phone);

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => 'budi@dealer.test', 'password' => 'rahasia123'])
            ->assertRedirect(route('mechanic.jobs.index'));
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidStaff(): array
    {
        return [
            'role owner tidak bisa dipilih' => [['role' => 'owner'], 'role'],
            'role customer' => [['role' => 'customer'], 'role'],
            'email sudah dipakai' => [['email' => 'joko@dealer.test'], 'email'],
            'kata sandi tidak sama' => [['password_confirmation' => 'beda12345'], 'password'],
            'kata sandi pendek' => [['password' => 'abc', 'password_confirmation' => 'abc'], 'password'],
            'nomor berhuruf' => [['phone' => '0812abc45678'], 'phone'],
        ];
    }

    #[DataProvider('invalidStaff')]
    public function test_validasi_tambah_staf(array $overrides, string $field): void
    {
        $this->actingAs($this->owner)->post(route('admin.staff.store'), [
            'name' => 'Staf Baru', 'email' => 'baru@dealer.test', 'role' => 'admin',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', ...$overrides,
        ])->assertSessionHasErrors($field);

        $this->assertSame(4, User::count());
    }

    public function test_admin_dan_mekanik_tidak_bisa_mengelola_staf(): void
    {
        $payload = ['name' => 'Penyusup', 'email' => 'x@dealer.test', 'role' => 'admin', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123'];

        $this->actingAs($this->admin)->post(route('admin.staff.store'), $payload)->assertForbidden();
        $this->actingAs($this->admin)->put(route('admin.staff.update', $this->mechanic), $payload)->assertForbidden();
        $this->actingAs($this->mechanic)->post(route('admin.staff.store'), $payload)->assertForbidden();

        $this->assertSame(4, User::count());
    }

    public function test_owner_dan_customer_tidak_bisa_diubah_lewat_menu_staf(): void
    {
        $this->actingAs($this->owner)->get(route('admin.staff.edit', $this->owner))->assertNotFound();
        $this->actingAs($this->owner)->get(route('admin.staff.edit', $this->customer))->assertNotFound();
        $this->actingAs($this->owner)->put(route('admin.staff.update', $this->owner), [
            'name' => 'Pak Hendra', 'email' => 'owner@dealer.test', 'role' => 'mechanic',
        ])->assertNotFound();

        $this->assertTrue($this->owner->fresh()->isOwner());
    }

    public function test_owner_mengubah_role_dan_menonaktifkan_staf(): void
    {
        $this->actingAs($this->owner)->put(route('admin.staff.update', $this->mechanic), [
            'name' => 'Joko Santoso', 'email' => 'joko@dealer.test', 'role' => 'admin', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $joko = $this->mechanic->fresh();
        $this->assertTrue($joko->isAdmin());
        $this->assertSame('Joko Santoso', $joko->name);
        $this->assertTrue(Hash::check('password', $joko->password), 'Kata sandi kosong = tidak diganti.');

        // Tanpa is_active = dinonaktifkan.
        $this->put(route('admin.staff.update', $joko), ['name' => 'Joko Santoso', 'email' => 'joko@dealer.test', 'role' => 'mechanic'])
            ->assertSessionHas('success', 'Akun Joko Santoso diperbarui dan dinonaktifkan (tidak bisa login).');
        $this->assertFalse($joko->fresh()->isActive());
    }

    public function test_staf_nonaktif_tidak_bisa_login_dan_sesinya_diakhiri(): void
    {
        $this->actingAs($this->mechanic)->get(route('mechanic.jobs.index'))->assertOk();

        $this->mechanic->forceFill(['is_active' => false])->save();

        // Sesi yang masih berjalan langsung diakhiri.
        $this->get(route('mechanic.jobs.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Akun Anda dinonaktifkan. Hubungi pemilik dealer.');
        $this->assertGuest();

        $this->post(route('login.store'), ['email' => $this->mechanic->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi pemilik dealer.']);
        $this->assertGuest();

        // Kata sandi salah tetap pesan umum (tidak membocorkan status akun).
        $this->post(route('login.store'), ['email' => $this->mechanic->email, 'password' => 'salah-sekali'])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);
    }

    public function test_notifikasi_admin_untuk_owner_dan_admin_aktif_saja(): void
    {
        $inactiveAdmin = User::factory()->admin()->inactive()->create();
        $testDrive = TestDrive::factory()->recycle($this->customer)->recycle(Car::factory()->create())->create();

        AdminActivityNotification::notifyAdmins(new TestDriveActivity($testDrive));

        $this->assertSame(1, $this->owner->notifications()->count());
        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertSame(0, $this->mechanic->notifications()->count());
        $this->assertSame(0, $inactiveAdmin->notifications()->count());
    }

    public function test_staf_tidak_memakai_fitur_customer(): void
    {
        foreach ([$this->owner, $this->mechanic] as $staff) {
            $this->actingAs($staff)->get(route('test-drives.create'))
                ->assertOk()
                ->assertSee(TestDriveRequest::ADMIN_MESSAGE);
            $this->actingAs($staff)->get(route('account.profile'))
                ->assertOk()
                ->assertDontSee(route('account.purchase-requests.index'), false);
        }

        // Mekanik tidak diberi tautan ke dashboard admin.
        $this->actingAs($this->mechanic)->get(route('test-drives.create'))
            ->assertDontSee(route('admin.test-drives.index'), false);
    }

    public function test_perintah_make_owner(): void
    {
        $this->artisan('user:make-owner', ['email' => $this->admin->email])
            ->expectsOutputToContain('sekarang owner')
            ->assertSuccessful();
        $this->assertTrue($this->admin->fresh()->isOwner());

        $this->artisan('user:make-owner', ['email' => 'tidak-ada@dealer.test'])->assertFailed();
    }

    public function test_seeder_admin_tidak_menurunkan_owner(): void
    {
        config(['dealer.admin.email' => $this->owner->email, 'dealer.admin.password' => 'rahasia123']);

        $this->seed(AdminUserSeeder::class);

        $this->assertTrue($this->owner->fresh()->isOwner());
    }

    public function test_menu_pengguna_menampilkan_role_staf(): void
    {
        $this->actingAs($this->admin)->get(route('admin.users.index', ['role' => 'mechanic']))
            ->assertOk()
            ->assertSee('Joko Mekanik')
            ->assertDontSee('Pak Hendra');
    }
}
