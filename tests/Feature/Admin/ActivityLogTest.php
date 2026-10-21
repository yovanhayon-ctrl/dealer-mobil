<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Car;
use App\Models\PaymentProof;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Staf > Riwayat Aktivitas: tindakan sensitif staf tercatat, aksi gagal tidak, halaman hanya untuk Owner & Super Admin.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-21 09:30:00', 'Asia/Jakarta'));
        $this->owner = User::factory()->owner()->create(['name' => 'Fadlan']);
    }

    private function makeApprovedPurchase(): PurchaseRequest
    {
        $car = Car::factory()->create(['brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id, 'name' => 'Navara VL', 'year' => 2024]);

        return PurchaseRequest::factory()->recycle($car)->status('approved')->create([
            'payment_method' => 'cash', 'car_price' => 560_000_000, 'created_at' => now()->subDays(2),
        ]);
    }

    /**
     * @return list<string>
     */
    private function descriptions(): array
    {
        return ActivityLog::query()->orderBy('id')->pluck('description')->all();
    }

    public function test_akun_staf_dan_login_staf_tercatat(): void
    {
        $this->actingAs($this->owner)->post(route('admin.staff.store'), [
            'name' => 'Rina Kartika', 'email' => 'rina@jaf.test', 'phone' => '081255501234', 'role' => 'sales',
            'password' => 'RahasiaBaru123', 'password_confirmation' => 'RahasiaBaru123',
        ])->assertSessionHasNoErrors();
        $rina = User::where('email', 'rina@jaf.test')->sole();

        $this->put(route('admin.staff.update', $rina), [
            'name' => 'Rina Kartika', 'email' => 'rina@jaf.test', 'phone' => '081255501234', 'role' => 'admin',
            'password' => 'GantiSandi456', 'password_confirmation' => 'GantiSandi456',
        ])->assertSessionHasNoErrors();
        // Tanpa perubahan: tidak dicatat.
        $this->put(route('admin.staff.update', $rina), [
            'name' => 'Rina Kartika', 'email' => 'rina@jaf.test', 'phone' => '081255501234', 'role' => 'admin', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame([
            'Menambah akun staf Rina Kartika sebagai Sales',
            'Mengubah akun Rina Kartika: role Sales → Admin Dealer, dinonaktifkan, kata sandi direset',
            'Mengubah akun Rina Kartika: diaktifkan',
        ], $this->descriptions());

        $log = ActivityLog::query()->where('action', 'staff.updated')->orderBy('id')->first();
        $this->assertSame(ActivityLog::CATEGORY_ACCOUNT, $log->category);
        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertEquals(['old' => 'sales', 'new' => 'admin'], $log->properties['role']);
        $this->assertSame('reset', $log->properties['password']);
        $this->assertSame('127.0.0.1', $log->ip_address);

        // Login: staf dicatat, customer tidak.
        auth()->logout();
        $this->post('/login', ['email' => 'rina@jaf.test', 'password' => 'GantiSandi456']);
        auth()->logout();
        $customer = User::factory()->create();
        $this->post('/login', ['email' => $customer->email, 'password' => 'password']);

        $login = ActivityLog::query()->where('category', ActivityLog::CATEGORY_LOGIN)->sole();
        $this->assertSame('Masuk', $login->description);
        $this->assertSame('Rina Kartika', $login->user_name);
        $this->assertSame('admin', $login->user_role);
    }

    public function test_keuangan_dan_status_pengajuan_tercatat(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Rina Admin']);
        $purchase = $this->makeApprovedPurchase();
        $number = $purchase->documentNumber();
        $proof = PaymentProof::factory()->recycle($purchase)->create([
            'purchase_request_id' => $purchase->id, 'type' => PaymentProof::TYPE_DOWN_PAYMENT, 'amount' => 10_000_000,
        ]);
        $second = PaymentProof::factory()->recycle($purchase)->create(['purchase_request_id' => $purchase->id, 'amount' => 5_000_000]);

        $this->actingAs($admin)->patch(route('admin.payment-proofs.approve', [$purchase, $proof]))->assertSessionHasNoErrors();
        // Tolak tanpa alasan: gagal validasi, tidak dicatat.
        $this->patch(route('admin.payment-proofs.reject', [$purchase, $second]), ['admin_note' => ''])->assertSessionHasErrors();
        $this->patch(route('admin.payment-proofs.reject', [$purchase, $second]), ['admin_note' => 'Nominal tidak sesuai mutasi.']);
        $this->patch(route('admin.purchase-requests.update-status', $purchase), [
            'status' => 'cancelled', 'admin_note' => 'Customer membatalkan pembelian.',
        ])->assertSessionHasNoErrors();
        $this->post(route('admin.payment-refunds.store', $purchase), [
            'amount' => '10.000.000', 'refunded_on' => '2026-10-21', 'method' => 'transfer',
        ])->assertSessionHasNoErrors();

        $this->assertSame([
            "Menerima bukti {$proof->typeLabel()} Rp 10.000.000, pengajuan {$number}",
            "Menolak bukti {$second->typeLabel()} Rp 5.000.000, pengajuan {$number}: Nominal tidak sesuai mutasi.",
            "Mengubah status pengajuan {$number}: Disetujui → Dibatalkan",
            "Mencatat pengembalian dana Rp 10.000.000, pengajuan {$number}",
        ], $this->descriptions());
        $this->assertSame(
            [ActivityLog::CATEGORY_FINANCE, ActivityLog::CATEGORY_FINANCE, ActivityLog::CATEGORY_PURCHASE, ActivityLog::CATEGORY_FINANCE],
            ActivityLog::query()->orderBy('id')->pluck('category')->all()
        );
        $this->assertEquals(['old' => 'approved', 'new' => 'cancelled'], ActivityLog::query()->where('action', 'purchase.status')->sole()->properties);

        // Sales tanpa hak verifikasi: ditolak dan tidak dicatat.
        $this->actingAs(User::factory()->sales()->create())->patch(route('admin.payment-proofs.approve', [$purchase, $second]))->assertForbidden();
        $this->assertSame(4, ActivityLog::count());
    }

    public function test_hapus_data_tercatat_dan_yang_gagal_tidak(): void
    {
        $unused = Brand::factory()->create(['name' => 'Datsun']);
        $used = Brand::factory()->create(['name' => 'Nissan']);
        Car::factory()->create(['brand_id' => $used->id]);

        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.brands.destroy', $unused))->assertForbidden();
        $this->actingAs($this->owner)->delete(route('admin.brands.destroy', $used));
        $this->delete(route('admin.brands.destroy', $unused))->assertRedirect(route('admin.brands.index'));

        $log = ActivityLog::query()->sole();
        $this->assertSame('Menghapus merek "Datsun"', $log->description);
        $this->assertSame(ActivityLog::CATEGORY_DELETE, $log->category);
        $this->assertSame($unused->id, $log->subject_id);
    }

    public function test_halaman_riwayat_hanya_owner_dan_super_admin(): void
    {
        $purchase = $this->makeApprovedPurchase();
        $sales = User::factory()->sales()->create(['name' => 'Yoseph']);
        ActivityLog::record(ActivityLog::CATEGORY_PURCHASE, 'purchase.status', 'Mengubah status pengajuan contoh', $purchase, user: $sales);
        $this->travel(1)->hours();
        ActivityLog::record(ActivityLog::CATEGORY_LOGIN, 'auth.login', 'Masuk', user: $this->owner);

        $this->actingAs($this->owner)->get(route('admin.staff.index'))->assertSee(route('admin.staff.activity'), false);
        $this->get(route('admin.staff.activity'))
            ->assertOk()
            ->assertSee('2 aktivitas')
            ->assertSee('href="'.route('admin.purchase-requests.show', $purchase).'"', false)
            ->assertSee('Yoseph')
            ->assertSee('Sales')
            ->assertSee('IP 127.0.0.1');

        $this->get(route('admin.staff.activity', ['kelompok' => 'login']))->assertSee('1 aktivitas ditemukan')->assertDontSee('Mengubah status pengajuan contoh');
        $this->get(route('admin.staff.activity', ['staf' => $sales->id]))->assertSee('1 aktivitas ditemukan')->assertSee('Mengubah status pengajuan contoh');
        $this->get(route('admin.staff.activity', ['dari' => '2026-10-22']))->assertSee('0 aktivitas ditemukan');
        $this->get(route('admin.staff.activity', ['kelompok' => 'tidak-ada', 'staf' => 'abc']))->assertSee('2 aktivitas');

        // Nama pelaku tetap tampil setelah akunnya dihapus.
        $sales->delete();
        $this->get(route('admin.staff.activity'))->assertSee('Yoseph');

        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.staff.activity'))->assertOk();
        foreach (['admin', 'sales', 'customerService', 'mechanic'] as $role) {
            $this->actingAs(User::factory()->{$role}()->create())->get(route('admin.staff.activity'))->assertForbidden();
        }
    }
}
