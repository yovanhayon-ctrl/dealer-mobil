<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PaymentProof;
use App\Models\PaymentRefund;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\PaymentRefundRecorded;
use App\Support\PurchaseRequestPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Pengembalian dana untuk pengajuan Dibatalkan/Ditolak yang sempat menerima pembayaran.
 */
class PaymentRefundTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private PurchaseRequest $purchase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->admin()->create(['name' => 'Rina Admin']);
        $this->customer = User::factory()->create(['name' => 'Fadlan']);
        $car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Navara VL 4x4 AT', 'year' => 2024, 'stock' => 0,
        ]);
        $this->purchase = PurchaseRequest::factory()->recycle($this->customer)->recycle($car)->status('approved')->create([
            'payment_method' => 'cash', 'car_price' => 560_000_000, 'created_at' => now()->subDays(5),
        ]);
        PaymentProof::factory()->recycle($this->purchase)->approved()->create([
            'purchase_request_id' => $this->purchase->id, 'amount' => 10_000_000,
        ]);
    }

    private function cancel(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.purchase-requests.update-status', $this->purchase), [
            'status' => 'cancelled', 'admin_note' => 'Pengajuan dibatalkan karena customer berubah pikiran.',
        ])->assertSessionHasNoErrors();
    }

    private function refund(array $overrides = []): TestResponse
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.purchase-requests.show', $this->purchase))
            ->post(route('admin.payment-refunds.store', $this->purchase), [
                'amount' => '10.000.000', 'refunded_on' => '2026-10-15', 'method' => 'transfer',
                'note' => 'Ditransfer ke BCA a.n. Fadlan', ...$overrides,
            ]);
    }

    private function refundStatus(): ?string
    {
        return $this->purchase->fresh()->refundStatus();
    }

    public function test_peringatan_sebelum_membatalkan_pengajuan_yang_sudah_dibayar(): void
    {
        $this->actingAs($this->admin)->get(route('admin.purchase-requests.show', $this->purchase))
            ->assertSee('Pengajuan ini sudah menerima')
            ->assertSee('kembalikan dananya lalu catat di bagian Bukti Pembayaran');

        $this->assertNull($this->refundStatus(), 'Pengajuan yang masih berjalan tidak punya status dana.');
        $this->assertSame('Dibayar sebagian', $this->purchase->fresh()->paymentStatusLabel());
    }

    public function test_status_dana_setelah_batal_lalu_dikembalikan_bertahap(): void
    {
        $this->cancel();
        $this->assertSame(PurchaseRequest::REFUND_PENDING, $this->refundStatus());
        $this->assertSame('Dana belum dikembalikan', $this->purchase->fresh()->paymentStatusLabel());
        $this->assertSame([$this->purchase->id], PurchaseRequest::query()->refundPending()->pluck('id')->all());

        $this->actingAs($this->admin)->get(route('admin.purchase-requests.show', $this->purchase))
            ->assertSee('belum dikembalikan ke customer')
            ->assertSee('Catat Pengembalian Dana')
            ->assertSee('value="10.000.000"', false);

        $this->refund(['amount' => '4.000.000'])
            ->assertRedirect(route('admin.purchase-requests.show', $this->purchase).'#bukti-pembayaran')
            ->assertSessionHas('success', 'Pengembalian dana Rp 4.000.000 dicatat. Status dana: Dikembalikan sebagian.');
        $this->assertSame(PurchaseRequest::REFUND_PARTIAL, $this->refundStatus());
        $this->assertSame([$this->purchase->id], PurchaseRequest::query()->refundPending()->pluck('id')->all(), 'Sisa masih perlu ditindaklanjuti.');

        $this->refund(['amount' => '6.000.000'])->assertSessionHasNoErrors();
        $this->assertSame(PurchaseRequest::REFUND_DONE, $this->refundStatus());
        $this->assertSame('Dikembalikan', $this->purchase->fresh()->paymentStatusLabel());
        $this->assertSame([], PurchaseRequest::query()->refundPending()->pluck('id')->all());

        $refund = PaymentRefund::query()->latest('id')->first();
        $this->assertSame($this->admin->id, $refund->recorded_by);
        $this->assertSame('transfer', $refund->method);

        $this->get(route('admin.purchase-requests.show', $this->purchase))
            ->assertDontSee('Catat Pengembalian Dana')
            ->assertSee('dicatat Rina Admin');
    }

    public function test_nominal_tidak_boleh_melebihi_dana_yang_belum_dikembalikan(): void
    {
        $this->cancel();

        $this->refund(['amount' => '10.000.001'])
            ->assertSessionHasErrorsIn('refund', ['amount' => 'Nominal melebihi dana yang belum dikembalikan Rp 10.000.000.']);
        $this->refund(['refunded_on' => '2026-10-16'])->assertSessionHasErrorsIn('refund', 'refunded_on');
        $this->refund(['method' => 'card'])->assertSessionHasErrorsIn('refund', 'method');

        $this->assertSame(0, PaymentRefund::query()->count());
    }

    public function test_hanya_untuk_pengajuan_batal_yang_punya_dana(): void
    {
        // Masih Disetujui: tidak ada dana yang bisa dikembalikan (form juga tidak tampil).
        $this->refund()->assertSessionHasErrorsIn('refund', 'amount');
        $this->get(route('admin.purchase-requests.show', $this->purchase))->assertDontSee('Catat Pengembalian Dana');

        // Batal tanpa pembayaran diterima.
        $unpaid = PurchaseRequest::factory()->recycle($this->customer)->status('cancelled')->create();
        $this->post(route('admin.payment-refunds.store', $unpaid), ['amount' => '1', 'refunded_on' => '2026-10-15', 'method' => 'cash']);
        $this->assertNull($unpaid->fresh()->refundStatus());

        $this->assertSame(0, PaymentRefund::query()->count());
    }

    public function test_tutup_tanpa_pengembalian_dengan_alasan(): void
    {
        $this->cancel();

        $this->patch(route('admin.payment-refunds.close', $this->purchase), ['refund_closed_note' => ''])
            ->assertSessionHasErrorsIn('refundClose', 'refund_closed_note');

        $this->patch(route('admin.payment-refunds.close', $this->purchase), ['refund_closed_note' => 'Tanda jadi hangus sesuai kesepakatan.'])
            ->assertSessionHas('success', 'Pengembalian dana ditutup. Status dana: Tidak dikembalikan.');

        $this->assertSame(PurchaseRequest::REFUND_WAIVED, $this->refundStatus());
        $this->assertSame([], PurchaseRequest::query()->refundPending()->pluck('id')->all());

        // Setelah ditutup, tidak bisa dicatat lagi.
        $this->refund()->assertSessionHas('status');
        $this->assertSame(0, PaymentRefund::query()->count());
    }

    public function test_sebagian_dikembalikan_lalu_sisanya_ditutup(): void
    {
        $this->cancel();
        $this->refund(['amount' => '7.500.000']);
        $this->patch(route('admin.payment-refunds.close', $this->purchase), ['refund_closed_note' => 'Potongan biaya administrasi sesuai kesepakatan.']);

        $this->assertSame(PurchaseRequest::REFUND_PARTIAL, $this->refundStatus());
        $this->assertFalse($this->purchase->fresh()->needsRefundAction());
    }

    public function test_customer_diberi_tahu_dan_melihat_rincian(): void
    {
        $this->cancel();

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertSee('Dana belum dikembalikan')
            ->assertSee('Dealer akan menghubungi Anda terkait dana');

        $this->refund(['amount' => '4.000.000']);

        $notification = $this->customer->notifications()->where('type', PaymentRefundRecorded::class)->sole();
        $this->assertSame('Dana Dikembalikan', $notification->data['title']);
        $this->assertSame(
            'Nissan Navara VL 4x4 AT 2024 · Cash · Rp 4.000.000 (Transfer) dikembalikan pada 15 Okt 2026. Sisa Rp 6.000.000 sedang diproses.',
            $notification->data['message'],
        );
        $this->assertSame('Dikembalikan sebagian', $notification->data['status_label']);
        $this->assertSame('Ditransfer ke BCA a.n. Fadlan', $notification->data['admin_note']);

        $this->patch(route('admin.payment-refunds.close', $this->purchase), ['refund_closed_note' => 'Sisa dipotong biaya administrasi.']);
        $closed = $this->customer->notifications()->where('type', PaymentRefundRecorded::class)->latest()->get()
            ->firstWhere('data.title', 'Dana Tidak Dikembalikan');
        $this->assertSame('Sisa dipotong biaya administrasi.', $closed->data['admin_note']);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertSee('Pengembalian dana')
            ->assertSee('Dana sisanya tidak dikembalikan:')
            ->assertSee('Sisa dipotong biaya administrasi.');

        $html = app(PurchaseRequestPdf::class)->html($this->purchase->fresh());
        $this->assertStringContainsString('Pengembalian Dana · Dikembalikan sebagian', $html);
        $this->assertStringContainsString('Rp 4.000.000', $html);
    }

    public function test_file_bukti_pengembalian_hanya_untuk_pemilik_dan_staf(): void
    {
        $this->cancel();
        $this->refund(['file' => UploadedFile::fake()->image('transfer-balik.jpg')])->assertSessionHasNoErrors();
        $refund = PaymentRefund::query()->sole();
        Storage::disk(PaymentRefund::DISK)->assertExists($refund->file_path);
        $this->assertStringStartsWith("payment-refunds/{$this->purchase->id}/", $refund->file_path);

        $this->actingAs($this->admin)->get(route('admin.payment-refunds.file', [$this->purchase, $refund]))->assertOk();
        $this->actingAs($this->customer)->get(route('account.payment-refunds.file', [$this->purchase, $refund]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        $other = User::factory()->create();
        $this->actingAs($other)->get(route('account.payment-refunds.file', [$this->purchase, $refund]))->assertNotFound();
        $this->actingAs($this->customer)->post(route('admin.payment-refunds.store', $this->purchase), [])->assertForbidden();
    }

    public function test_filter_dan_dashboard(): void
    {
        $this->cancel();

        $this->actingAs($this->admin)->get(route('admin.purchase-requests.index', ['pembayaran' => 'dana-belum-dikembalikan']))
            ->assertViewHas('purchaseRequests', fn ($list) => $list->pluck('id')->all() === [$this->purchase->id])
            ->assertSee('Dana belum dikembalikan');

        $this->get(route('admin.dashboard'))
            ->assertSee('<strong>1</strong> pengajuan batal, dana belum dikembalikan', false)
            ->assertSee(route('admin.purchase-requests.index', ['pembayaran' => 'dana-belum-dikembalikan']), false);

        $this->refund();
        $this->get(route('admin.dashboard'))->assertDontSee('dana belum dikembalikan');
    }
}
