<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PaymentProof;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\PurchaseRequestPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Bukti pembayaran: customer mengunggah (pengajuan disetujui), admin menerima / menolak, file hanya untuk yang berhak.
 */
class PaymentProofTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    private PurchaseRequest $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-12 10:00:00', 'Asia/Jakarta'));
        config(['dealer.bank' => ['name' => 'BCA', 'account' => '1234567890', 'holder' => 'PT JAF Dealer']]);
        $this->customer = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
        $car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Kicks e-Power VL', 'year' => 2025, 'stock' => 3,
        ]);
        $this->cash = PurchaseRequest::factory()->recycle($this->customer)->recycle($car)->create([
            'payment_method' => 'cash', 'car_price' => 505_000_000, 'status' => PurchaseRequest::STATUS_APPROVED,
            'created_at' => now()->subDays(3),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'type' => PaymentProof::TYPE_BOOKING_FEE,
            'amount' => 'Rp 5.000.000',
            'paid_on' => '2026-10-11',
            'file' => UploadedFile::fake()->image('Bukti BCA.jpg', 800, 1200),
            'note' => 'Transfer dari rekening istri.',
            ...$overrides,
        ];
    }

    private function upload(array $overrides = [], ?PurchaseRequest $purchase = null, ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->customer)
            ->from(route('account.payment-proofs.create', $purchase ?? $this->cash))
            ->post(route('account.payment-proofs.store', $purchase ?? $this->cash), $this->payload($overrides));
    }

    private function proof(array $attributes = [], ?PurchaseRequest $purchase = null): PaymentProof
    {
        $purchase ??= $this->cash;
        $proof = PaymentProof::factory()->recycle($purchase)->create(['purchase_request_id' => $purchase->id, ...$attributes]);
        Storage::disk(PaymentProof::DISK)->put($proof->file_path, 'isi-file');

        return $proof;
    }

    public function test_customer_mengunggah_bukti_tersimpan_privat_dan_menunggu_verifikasi(): void
    {
        $this->upload()
            ->assertRedirect(route('account.purchase-requests.index')."#pengajuan-{$this->cash->id}")
            ->assertSessionHas('success', 'Bukti pembayaran terkirim dan menunggu verifikasi admin.');

        $proof = PaymentProof::query()->sole();
        $this->assertSame(PaymentProof::STATUS_PENDING, $proof->status);
        $this->assertSame(5_000_000, $proof->amount);
        $this->assertSame('2026-10-11', $proof->paid_on->toDateString());
        $this->assertSame('Bukti BCA.jpg', $proof->file_name);
        $this->assertSame($this->customer->id, $proof->user_id);
        $this->assertStringStartsWith("payment-proofs/{$this->cash->id}/", $proof->file_path);
        $this->assertStringNotContainsString('Bukti BCA', $proof->file_path);
        Storage::disk(PaymentProof::DISK)->assertExists($proof->file_path);
        Storage::disk('public')->assertDirectoryEmpty('/');
    }

    public function test_pdf_dari_m_banking_diterima(): void
    {
        $this->upload(['file' => UploadedFile::fake()->create('mutasi.pdf', 300, 'application/pdf')])->assertSessionHasNoErrors();

        $this->assertStringEndsWith('.pdf', PaymentProof::query()->sole()->file_path);
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'jenis kosong' => [['type' => ''], 'type', 'Jenis pembayaran wajib diisi'],
            'DP untuk cash' => [['type' => PaymentProof::TYPE_DOWN_PAYMENT], 'type', 'tidak valid'],
            'nominal berhuruf' => [['amount' => '5jt'], 'amount', 'Nominal hanya boleh berisi angka.'],
            'nominal melebihi harga' => [['amount' => '505.000.001'], 'amount', 'Nominal tidak boleh melebihi Rp 505.000.000 (harga mobil).'],
            'tanggal masa depan' => [['paid_on' => '2026-10-13'], 'paid_on', 'Tanggal transfer tidak boleh di masa depan.'],
            'tanggal sebelum pengajuan' => [['paid_on' => '2026-10-08'], 'paid_on', 'Tanggal transfer tidak boleh sebelum tanggal pengajuan.'],
            'file kosong' => [['file' => null], 'file', 'File bukti wajib diisi'],
            'file svg' => [['file' => UploadedFile::fake()->create('bukti.svg', 10, 'image/svg+xml')], 'file', 'JPG, PNG, WEBP, atau PDF'],
            'file teks' => [['file' => UploadedFile::fake()->create('bukti.txt', 10, 'text/plain')], 'file', 'JPG, PNG, WEBP, atau PDF'],
            'file terlalu besar' => [['file' => UploadedFile::fake()->create('bukti.pdf', 8 * 1024 + 1, 'application/pdf')], 'file', 'maksimal 8 MB'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_validasi_menolak_isian_tidak_valid(array $overrides, string $field, string $message): void
    {
        $this->upload($overrides)->assertSessionHasErrors($field);

        $this->assertStringContainsString($message, session('errors')->first($field));
        $this->assertSame(0, PaymentProof::query()->count());
    }

    public function test_jenis_pembayaran_mengikuti_metode(): void
    {
        $credit = PurchaseRequest::factory()->recycle($this->customer)->credit()->create([
            'status' => PurchaseRequest::STATUS_APPROVED, 'down_payment' => 100_000_000, 'created_at' => now()->subDay(),
        ]);

        $this->assertSame(['booking_fee' => 'Tanda jadi', 'settlement' => 'Pelunasan'], $this->cash->paymentProofTypes());
        $this->assertSame(['booking_fee' => 'Tanda jadi', 'down_payment' => 'DP'], $credit->paymentProofTypes());

        $this->upload(['type' => PaymentProof::TYPE_SETTLEMENT], $credit)->assertSessionHasErrors('type');
        $this->upload(['amount' => '100.000.001', 'type' => PaymentProof::TYPE_DOWN_PAYMENT], $credit)
            ->assertSessionHasErrors(['amount' => 'Nominal tidak boleh melebihi Rp 100.000.000 (DP pengajuan ini).']);
        $this->upload(['amount' => '100.000.000', 'type' => PaymentProof::TYPE_DOWN_PAYMENT], $credit)->assertSessionHasNoErrors();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function closedStatuses(): array
    {
        return [
            'menunggu' => [PurchaseRequest::STATUS_PENDING],
            'diproses' => [PurchaseRequest::STATUS_PROCESSING],
            'selesai' => [PurchaseRequest::STATUS_COMPLETED],
            'dibatalkan' => [PurchaseRequest::STATUS_CANCELLED],
        ];
    }

    #[DataProvider('closedStatuses')]
    public function test_hanya_pengajuan_disetujui_yang_bisa_mengirim_bukti(string $status): void
    {
        $this->cash->update(['status' => $status]);
        $message = 'Bukti pembayaran hanya bisa dikirim untuk pengajuan yang sudah disetujui.';

        $this->actingAs($this->customer)->get(route('account.payment-proofs.create', $this->cash))
            ->assertRedirect(route('account.purchase-requests.index'))->assertSessionHas('error', $message);
        $this->upload()->assertRedirect(route('account.purchase-requests.index'))->assertSessionHas('error', $message);
        $this->assertSame(0, PaymentProof::query()->count());
        $this->get(route('account.purchase-requests.index'))->assertDontSee('Kirim Bukti Pembayaran');
    }

    public function test_pengajuan_milik_orang_lain_404_tanpa_membocorkan_nominal(): void
    {
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('account.payment-proofs.create', $this->cash))->assertNotFound();
        $this->upload(['amount' => '999.999.999.999'], user: $other)->assertNotFound()->assertDontSee('505.000.000');
        $this->assertSame(0, PaymentProof::query()->count());
    }

    public function test_batas_jumlah_bukti_per_pengajuan(): void
    {
        PaymentProof::factory()->count(PaymentProof::MAX_PER_PURCHASE)->recycle($this->cash)->rejected()->create();

        $this->upload()->assertRedirect(route('account.purchase-requests.index'))->assertSessionHas('error');
        $this->assertSame(PaymentProof::MAX_PER_PURCHASE, PaymentProof::query()->count());
    }

    public function test_pengajuan_saya_menampilkan_rekening_riwayat_dan_total_terverifikasi(): void
    {
        $this->proof(['amount' => 5_000_000])->update(['status' => PaymentProof::STATUS_APPROVED, 'verified_at' => now()]);
        $rejected = $this->proof(['type' => PaymentProof::TYPE_SETTLEMENT, 'amount' => 500_000_000]);
        $rejected->update(['status' => PaymentProof::STATUS_REJECTED, 'admin_note' => 'Dana belum masuk.']);
        $pending = $this->proof(['type' => PaymentProof::TYPE_SETTLEMENT, 'amount' => 500_000_000]);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertOk()
            ->assertSee('Pembayaran ke dealer')
            ->assertSee('BCA 1234567890')
            ->assertSee('PT JAF Dealer')
            ->assertSeeInOrder(['Terverifikasi', 'Rp 5.000.000', 'dari', 'Rp 505.000.000'])
            ->assertSee('Menunggu verifikasi')
            ->assertSee('Diterima')
            ->assertSee('Ditolak:')
            ->assertSee('Dana belum masuk.')
            ->assertSee(route('account.payment-proofs.file', [$this->cash, $pending]), false)
            ->assertSee(route('account.payment-proofs.create', $this->cash), false)
            ->assertSee('kirim bukti transfernya di menu Pengajuan Saya');
    }

    public function test_rekening_tidak_tampil_bila_belum_diisi(): void
    {
        config(['dealer.bank' => ['name' => '', 'account' => '', 'holder' => '']]);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertSee('Kirim Bukti Pembayaran')
            ->assertDontSee('Transfer ke');
        $this->get(route('account.payment-proofs.create', $this->cash))->assertOk()->assertDontSee('Transfer ke');
    }

    public function test_file_hanya_untuk_pemilik_dan_admin(): void
    {
        $proof = $this->proof();
        $customerUrl = route('account.payment-proofs.file', [$this->cash, $proof]);
        $adminUrl = route('admin.payment-proofs.file', [$this->cash, $proof]);

        $this->get($customerUrl)->assertRedirect(route('login'));

        $response = $this->actingAs($this->customer)->get($customerUrl)->assertOk();
        $this->assertSame('isi-file', $response->streamedContent());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get($adminUrl)->assertForbidden();

        $this->actingAs(User::factory()->create())->get($customerUrl)->assertNotFound();

        $this->actingAs($this->admin)->get($adminUrl)->assertOk();
    }

    public function test_bukti_harus_milik_pengajuan_di_url(): void
    {
        $proof = $this->proof();
        $otherPurchase = PurchaseRequest::factory()->recycle($this->customer)->create(['status' => PurchaseRequest::STATUS_APPROVED]);

        $this->actingAs($this->customer)->get(route('account.payment-proofs.file', [$otherPurchase, $proof]))->assertNotFound();
        $this->actingAs($this->admin)->get(route('admin.payment-proofs.file', [$otherPurchase, $proof]))->assertNotFound();
        $this->patch(route('admin.payment-proofs.approve', [$otherPurchase, $proof]))->assertNotFound();
        $this->assertTrue($proof->fresh()->isPending());
    }

    public function test_admin_menerima_bukti(): void
    {
        $proof = $this->proof(['amount' => 5_000_000]);

        $this->actingAs($this->admin)->get(route('admin.purchase-requests.show', $this->cash))
            ->assertOk()
            ->assertSee('Bukti Pembayaran')
            ->assertSee('Transfer 12 Okt 2026')
            ->assertSee(route('admin.payment-proofs.approve', [$this->cash, $proof]), false)
            ->assertSee(route('admin.payment-proofs.reject', [$this->cash, $proof]), false);

        $this->patch(route('admin.payment-proofs.approve', [$this->cash, $proof]))
            ->assertRedirect(route('admin.purchase-requests.show', $this->cash).'#bukti-pembayaran')
            ->assertSessionHas('success', 'Bukti Tanda jadi Rp 5.000.000 diterima. Status pembayaran: Dibayar sebagian.');

        $proof->refresh();
        $this->assertSame(PaymentProof::STATUS_APPROVED, $proof->status);
        $this->assertNotNull($proof->verified_at);
        $this->assertSame(5_000_000, $this->cash->fresh()->verifiedPaymentTotal());
        $this->assertSame(PurchaseRequest::STATUS_APPROVED, $this->cash->fresh()->status);

        $this->patch(route('admin.payment-proofs.approve', [$this->cash, $proof]))
            ->assertSessionHas('status', 'Bukti pembayaran ini sudah diterima.');
        $this->get(route('admin.purchase-requests.show', $this->cash))
            ->assertDontSee(route('admin.payment-proofs.approve', [$this->cash, $proof]), false);
    }

    public function test_admin_menolak_bukti_dengan_alasan(): void
    {
        $proof = $this->proof();
        $url = route('admin.payment-proofs.reject', [$this->cash, $proof]);

        $this->actingAs($this->admin)->from(route('admin.purchase-requests.show', $this->cash))
            ->patch($url, ['admin_note' => ''])
            ->assertSessionHasErrorsIn("rejectProof{$proof->id}", 'admin_note');
        $this->assertTrue($proof->fresh()->isPending());

        $this->patch($url, ['admin_note' => 'Dana belum masuk ke rekening dealer.'])
            ->assertSessionHas('success');

        $proof->refresh();
        $this->assertSame(PaymentProof::STATUS_REJECTED, $proof->status);
        $this->assertSame('Dana belum masuk ke rekening dealer.', $proof->admin_note);

        $this->patch(route('admin.payment-proofs.approve', [$this->cash, $proof]))
            ->assertSessionHas('status', 'Bukti pembayaran ini sudah ditolak.');
        $this->assertSame(PaymentProof::STATUS_REJECTED, $proof->fresh()->status);
    }

    public function test_customer_tidak_bisa_memverifikasi(): void
    {
        $proof = $this->proof();

        $this->actingAs($this->customer)->patch(route('admin.payment-proofs.approve', [$this->cash, $proof]))->assertForbidden();
        $this->assertTrue($proof->fresh()->isPending());
    }

    public function test_status_pembayaran_cash_dari_belum_dibayar_hingga_lunas(): void
    {
        $this->assertSame(['unpaid', 'Belum dibayar'], [$this->cash->paymentStatus(), $this->cash->paymentStatusLabel()]);

        $booking = $this->proof(['amount' => 5_000_000]);
        $settlement = $this->proof(['type' => PaymentProof::TYPE_SETTLEMENT, 'amount' => 500_000_000]);

        $this->actingAs($this->admin)->patch(route('admin.payment-proofs.approve', [$this->cash, $booking]));
        $cash = $this->cash->fresh();
        $this->assertSame(['partial', 'Dibayar sebagian', 500_000_000], [$cash->paymentStatus(), $cash->paymentStatusLabel(), $cash->remainingPayment()]);

        $this->patch(route('admin.payment-proofs.approve', [$this->cash, $settlement]))
            ->assertSessionHas('success', 'Bukti Pelunasan Rp 500.000.000 diterima. Status pembayaran: Lunas.');
        $cash = $this->cash->fresh();
        $this->assertSame(['paid', 'Lunas', 0], [$cash->paymentStatus(), $cash->paymentStatusLabel(), $cash->remainingPayment()]);
        $this->assertTrue($cash->isPaidOff());
        // Status pengajuan tetap manual (Selesai = mobil sudah diserahkan).
        $this->assertSame(PurchaseRequest::STATUS_APPROVED, $cash->status);

        $this->get(route('admin.purchase-requests.show', $this->cash))
            ->assertSee('Lunas')
            ->assertSee('Ubah status ke');
        $this->get(route('admin.purchase-requests.index'))->assertSee('Lunas');
    }

    public function test_status_pembayaran_kredit_memakai_dp(): void
    {
        $credit = PurchaseRequest::factory()->recycle($this->customer)->credit()->create([
            'status' => PurchaseRequest::STATUS_APPROVED, 'down_payment' => 100_000_000,
        ]);

        $this->proof(['amount' => 40_000_000], $credit)->update(['status' => PaymentProof::STATUS_APPROVED]);
        $this->assertSame('DP sebagian', $credit->fresh()->paymentStatusLabel());

        $this->proof(['type' => PaymentProof::TYPE_DOWN_PAYMENT, 'amount' => 60_000_000], $credit)->update(['status' => PaymentProof::STATUS_APPROVED]);
        $this->assertSame('DP lunas', $credit->fresh()->paymentStatusLabel());
    }

    public function test_admin_tidak_bisa_menerima_bukti_yang_melebihi_tagihan(): void
    {
        $first = $this->proof(['type' => PaymentProof::TYPE_SETTLEMENT, 'amount' => 505_000_000]);
        $duplicate = $this->proof(['type' => PaymentProof::TYPE_SETTLEMENT, 'amount' => 505_000_000]);

        $this->actingAs($this->admin)->patch(route('admin.payment-proofs.approve', [$this->cash, $first]));
        $this->patch(route('admin.payment-proofs.approve', [$this->cash, $duplicate]))
            ->assertSessionHas('error', 'Bukti tidak bisa diterima: total pembayaran akan melebihi Rp 505.000.000 (sisa Rp 0). Periksa lagi atau tolak bukti ini.');

        $this->assertTrue($duplicate->fresh()->isPending());
        $this->assertSame(505_000_000, $this->cash->fresh()->verifiedPaymentTotal());
    }

    public function test_setelah_lunas_customer_tidak_bisa_mengirim_bukti_lagi(): void
    {
        $this->proof(['type' => PaymentProof::TYPE_SETTLEMENT, 'amount' => 505_000_000])->update(['status' => PaymentProof::STATUS_APPROVED]);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertSee('Lunas')
            ->assertSee('Pembayaran lunas. Tim kami akan menghubungi Anda untuk jadwal serah terima.')
            ->assertDontSee('Kirim Bukti Pembayaran')
            ->assertDontSee('Transfer ke');

        $this->upload()->assertRedirect(route('account.purchase-requests.index'))
            ->assertSessionHas('status', 'Pembayaran pengajuan ini sudah lunas. Tidak perlu mengirim bukti lagi.');
        $this->assertSame(1, PaymentProof::query()->count());
    }

    public function test_pengajuan_saya_menampilkan_jumlah_bukti_menunggu(): void
    {
        $this->proof();
        $this->proof();

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertSee('Belum dibayar')
            ->assertSee('2 bukti menunggu verifikasi admin.');
    }

    public function test_pdf_memuat_status_dan_daftar_pembayaran_diterima(): void
    {
        $this->proof(['amount' => 5_000_000, 'paid_on' => '2026-10-10'])->update(['status' => PaymentProof::STATUS_APPROVED]);
        $this->proof(['type' => PaymentProof::TYPE_SETTLEMENT, 'amount' => 1_000_000]);

        $html = app(PurchaseRequestPdf::class)->html($this->cash->fresh());

        $this->assertStringContainsString('Pembayaran: <span class="status">Dibayar sebagian</span>', $html);
        $this->assertStringContainsString('Pembayaran Diterima', $html);
        $this->assertStringContainsString('Tanda jadi · 10 Oktober 2026', $html);
        $this->assertStringContainsString('Rp 5.000.000', $html);
        $this->assertStringContainsString('Sisa pembayaran', $html);
        $this->assertStringContainsString('Rp 500.000.000', $html);
        $this->assertStringNotContainsString('Rp 1.000.000<', $html);
    }

    public function test_detail_admin_tanpa_kartu_bila_belum_disetujui_dan_belum_ada_bukti(): void
    {
        $this->cash->update(['status' => PurchaseRequest::STATUS_PROCESSING]);

        $this->actingAs($this->admin)->get(route('admin.purchase-requests.show', $this->cash))
            ->assertOk()
            ->assertDontSee('id="bukti-pembayaran"', false);
    }
}
