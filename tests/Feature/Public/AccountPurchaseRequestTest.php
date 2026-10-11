<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pengajuan Saya (/akun/pengajuan) + pembatalan oleh customer.
 */
class AccountPurchaseRequestTest extends TestCase
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
            'name' => 'Serena e-Power', 'year' => 2025, 'stock' => 2,
        ]);
    }

    private function purchase(array $attributes = [], ?User $user = null): PurchaseRequest
    {
        return PurchaseRequest::factory()->recycle($user ?? $this->customer)->recycle($this->car)->create([
            'payment_method' => 'credit', 'car_price' => 650_000_000, 'down_payment' => 130_000_000,
            'tenor_months' => 36, 'interest_rate' => 6, 'monthly_installment' => 17_045_000,
            'address' => 'Jl. Merdeka No. 10, Bandung', 'status' => 'pending',
            ...$attributes,
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $purchase = $this->purchase();

        $this->get(route('account.purchase-requests.index'))->assertRedirect(route('login'));
        $this->patch(route('account.purchase-requests.cancel', $purchase))->assertRedirect(route('login'));

        $this->assertSame('pending', $purchase->fresh()->status);
    }

    public function test_kosong_menampilkan_empty_state(): void
    {
        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertOk()
            ->assertSee('Belum ada pengajuan');
    }

    public function test_hanya_milik_sendiri_dengan_rincian_kredit_dan_catatan_dealer(): void
    {
        $this->purchase(['status' => 'processing', 'notes' => 'Warna hitam.', 'admin_note' => 'Dokumen sedang dicek.']);
        $other = Car::factory()->create(['name' => 'Mobil Orang Lain']);
        PurchaseRequest::factory()->recycle($other)->create();

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertOk()
            ->assertSeeInOrder(['Nissan Serena e-Power 2025', 'Diproses', 'Kredit', 'Rp 650.000.000',
                'DP', 'Rp 130.000.000', 'Tenor 36 bulan, bunga 6%/tahun', 'Rp 17.045.000', '/bulan', 'Rp '.number_format(130_000_000 + 17_045_000 * 36, 0, ',', '.')])
            ->assertSee('Jl. Merdeka No. 10, Bandung')
            ->assertSee('Warna hitam.')
            ->assertSee('Dokumen sedang dicek.')
            ->assertDontSee('Mobil Orang Lain');
    }

    public function test_cash_tanpa_rincian_kredit(): void
    {
        $this->purchase(['payment_method' => 'cash', 'down_payment' => null, 'tenor_months' => null, 'interest_rate' => null, 'monthly_installment' => null]);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertSee('Cash')
            ->assertDontSee('/bulan');
    }

    public function test_tombol_batalkan_hanya_untuk_pending(): void
    {
        $pending = $this->purchase();
        $processing = $this->purchase(['status' => 'processing']);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertSee(route('account.purchase-requests.cancel', $pending))
            ->assertDontSee(route('account.purchase-requests.cancel', $processing));
    }

    public function test_batal_saat_pending_tanpa_mengubah_stok_dan_catatan_admin(): void
    {
        $purchase = $this->purchase(['admin_note' => 'Menunggu dokumen.']);

        $this->actingAs($this->customer)
            ->from(route('account.purchase-requests.index'))
            ->patch(route('account.purchase-requests.cancel', $purchase))
            ->assertRedirect(route('account.purchase-requests.index'))
            ->assertSessionHas('success', 'Pengajuan pembelian berhasil dibatalkan.');

        $purchase->refresh();
        $this->assertSame('cancelled', $purchase->status);
        $this->assertSame('Menunggu dokumen.', $purchase->admin_note);
        $this->assertSame(2, $this->car->fresh()->stock);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notCancellable(): array
    {
        return [
            'diproses' => ['processing'],
            'disetujui' => ['approved'],
            'ditolak' => ['rejected'],
            'selesai' => ['completed'],
        ];
    }

    #[DataProvider('notCancellable')]
    public function test_tidak_bisa_batal_selain_pending(string $status): void
    {
        $purchase = $this->purchase(['status' => $status]);

        $this->actingAs($this->customer)
            ->patch(route('account.purchase-requests.cancel', $purchase))
            ->assertSessionHas('error', 'Pengajuan yang sudah diproses tidak bisa dibatalkan dari sini. Silakan hubungi dealer.');

        $this->assertSame($status, $purchase->fresh()->status);
        $this->assertSame(2, $this->car->fresh()->stock);
    }

    public function test_klik_ganda_batal_aman(): void
    {
        $purchase = $this->purchase();

        $this->actingAs($this->customer)->patch(route('account.purchase-requests.cancel', $purchase))->assertSessionHas('success');
        $this->actingAs($this->customer)->patch(route('account.purchase-requests.cancel', $purchase))
            ->assertSessionHas('status', 'Pengajuan ini sudah dibatalkan.');
    }

    public function test_tidak_bisa_membatalkan_milik_orang_lain(): void
    {
        $purchase = $this->purchase([], User::factory()->create());

        $this->actingAs($this->customer)
            ->patch(route('account.purchase-requests.cancel', $purchase))
            ->assertNotFound();

        $this->assertSame('pending', $purchase->fresh()->status);
    }

    public function test_dropdown_navbar_pengajuan_saya_hanya_untuk_customer(): void
    {
        $this->actingAs($this->customer)->get(route('home'))
            ->assertSee('Pengajuan Saya')
            ->assertSee(route('account.purchase-requests.index'));

        $this->actingAs(User::factory()->admin()->create())->get(route('home'))
            ->assertDontSee('Pengajuan Saya');
    }

    public function test_jumlah_query_tetap_walau_data_bertambah(): void
    {
        $this->purchase();

        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $before = $count();
        PurchaseRequest::factory()->count(4)->recycle($this->customer)->create();
        $this->assertSame($before, $count());
    }
}
