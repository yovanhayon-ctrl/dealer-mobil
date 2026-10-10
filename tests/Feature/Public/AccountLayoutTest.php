<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Halaman akun customer: menu akun bersama dan penyaring status riwayat.
 */
class AccountLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create();
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Serena e-Power', 'year' => 2025, 'stock' => 5,
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function accountPages(): array
    {
        return [
            'profil' => ['account.profile'],
            'pengajuan' => ['account.purchase-requests.index'],
            'test drive' => ['account.test-drives.index'],
            'servis' => ['account.service-bookings.index'],
            'favorit' => ['account.favorites.index'],
            'notifikasi' => ['account.notifications.index'],
        ];
    }

    #[DataProvider('accountPages')]
    public function test_menu_akun_tampil_di_setiap_halaman_akun_dengan_menu_aktif(string $route): void
    {
        $html = $this->actingAs($this->customer)->get(route($route))->assertOk()->getContent();

        $this->assertStringContainsString('class="account-nav"', $html);
        foreach (array_column(self::accountPages(), 0) as $menuRoute) {
            $this->assertStringContainsString('href="'.route($menuRoute).'"', $html);
        }
        $this->assertMatchesRegularExpression('#class="account-nav-link active" aria-current="page"[^>]*>|href="'.preg_quote(route($route), '#').'" class="account-nav-link active"#', $html);
    }

    public function test_menu_akun_menampilkan_jumlah_data(): void
    {
        PurchaseRequest::factory()->count(2)->recycle($this->customer)->recycle($this->car)->create();
        TestDrive::factory()->recycle($this->customer)->recycle($this->car)->create();

        $this->actingAs($this->customer)->get(route('account.profile'))
            ->assertOk()
            ->assertSeeInOrder(['account-nav', 'Pengajuan', '<span class="account-nav-count">2</span>', 'Test Drive', '<span class="account-nav-count">1</span>'], false);
    }

    public function test_admin_hanya_melihat_profil_dan_dashboard_di_menu_akun(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())->get(route('account.profile'))->assertOk()->getContent();

        $nav = substr($html, strpos($html, 'class="account-nav"'), 1500);
        $this->assertStringContainsString('Dashboard Admin', $nav);
        $this->assertStringNotContainsString('href="'.route('account.purchase-requests.index').'"', $nav);
    }

    public function test_penyaring_status_pengajuan(): void
    {
        $pending = PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->create(['address' => 'Alamat Berjalan']);
        $done = PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->status('completed')->create(['address' => 'Alamat Selesai']);
        $cancelled = PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->status('cancelled')->create(['address' => 'Alamat Batal']);
        PurchaseRequest::factory()->recycle(User::factory()->create())->recycle($this->car)->create(['address' => 'Alamat Orang Lain']);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertOk()
            ->assertSee('3 pengajuan pembelian')
            ->assertSeeInOrder(['Semua', '3', 'Berjalan', '1', 'Selesai', '1', 'Dibatalkan', '1'])
            ->assertSee('Alamat Berjalan')->assertSee('Alamat Selesai')->assertSee('Alamat Batal')
            ->assertDontSee('Alamat Orang Lain');

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index', ['status' => 'selesai']))
            ->assertOk()
            ->assertSee('Alamat Selesai')
            ->assertDontSee('Alamat Berjalan')
            ->assertDontSee('Alamat Batal')
            ->assertSee('id="pengajuan-'.$done->id.'"', false);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index', ['status' => 'dibatalkan']))
            ->assertSee('id="pengajuan-'.$cancelled->id.'"', false)
            ->assertDontSee('id="pengajuan-'.$pending->id.'"', false);

        // Nilai tidak dikenal = semua.
        $this->actingAs($this->customer)->get(route('account.purchase-requests.index', ['status' => 'xx']))
            ->assertSee('Alamat Berjalan')->assertSee('Alamat Selesai');
    }

    public function test_penyaring_status_kosong_menampilkan_tombol_semua(): void
    {
        TestDrive::factory()->recycle($this->customer)->recycle($this->car)->create();

        $this->actingAs($this->customer)->get(route('account.test-drives.index', ['status' => 'selesai']))
            ->assertOk()
            ->assertSee('Tidak ada test drive dengan status ini')
            ->assertSee('Tampilkan Semua');
    }

    public function test_penyaring_status_servis_dan_test_drive(): void
    {
        ServiceBooking::factory()->recycle($this->customer)->status('in_progress')->create(['plate_number' => 'B 1 JLN']);
        ServiceBooking::factory()->recycle($this->customer)->status('completed')->create(['plate_number' => 'B 2 SLS']);
        TestDrive::factory()->recycle($this->customer)->recycle($this->car)->status('confirmed')->create(['notes' => 'Catatan berjalan']);
        TestDrive::factory()->recycle($this->customer)->recycle($this->car)->status('cancelled')->create(['notes' => 'Catatan batal']);

        $this->actingAs($this->customer)->get(route('account.service-bookings.index', ['status' => 'berjalan']))
            ->assertOk()
            ->assertSee('B 1 JLN')
            ->assertDontSee('B 2 SLS');

        $this->actingAs($this->customer)->get(route('account.test-drives.index', ['status' => 'dibatalkan']))
            ->assertOk()
            ->assertSee('Catatan batal')
            ->assertDontSee('Catatan berjalan');
    }

    public function test_pagination_mempertahankan_penyaring(): void
    {
        PurchaseRequest::factory()->count(12)->recycle($this->customer)->recycle($this->car)->status('completed')->create();

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index', ['status' => 'selesai']))
            ->assertOk()
            ->assertSee('status=selesai&amp;page=2', false);
    }
}
