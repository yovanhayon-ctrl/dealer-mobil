<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PaymentProof;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\Admin\PaymentProofActivity;
use App\Notifications\PaymentProofReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Notifikasi bukti pembayaran: bukti baru ke owner & admin aktif; diterima / ditolak / pembayaran showroom ke customer.
 */
class PaymentProofNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    private User $owner;

    private PurchaseRequest $cash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-12 10:00:00', 'Asia/Jakarta'));
        $this->customer = User::factory()->create(['name' => 'Fadlan']);
        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->owner()->create();
        $car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Kicks e-Power VL', 'year' => 2025, 'stock' => 3,
        ]);
        $this->cash = PurchaseRequest::factory()->recycle($this->customer)->recycle($car)->create([
            'payment_method' => 'cash', 'car_price' => 505_000_000, 'status' => PurchaseRequest::STATUS_APPROVED,
            'created_at' => now()->subDays(3),
        ]);
    }

    private function proof(array $attributes = [], ?PurchaseRequest $purchase = null): PaymentProof
    {
        $purchase ??= $this->cash;
        $proof = PaymentProof::factory()->recycle($purchase)->create(['purchase_request_id' => $purchase->id, ...$attributes]);
        Storage::disk(PaymentProof::DISK)->put($proof->file_path, 'isi-file');

        return $proof;
    }

    public function test_bukti_baru_dikirim_ke_owner_dan_admin_aktif_saja(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $inactiveAdmin = User::factory()->admin()->inactive()->create();

        $this->actingAs($this->customer)->post(route('account.payment-proofs.store', $this->cash), [
            'type' => PaymentProof::TYPE_BOOKING_FEE, 'amount' => 'Rp 5.000.000', 'paid_on' => '2026-10-11',
            'file' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertSessionHasNoErrors();

        foreach ([$this->admin, $this->owner] as $staff) {
            $notification = $staff->notifications()->sole();
            $this->assertSame(PaymentProofActivity::class, $notification->type);
            $this->assertSame([
                'title' => 'Bukti Pembayaran Baru · Fadlan',
                'message' => 'Nissan Kicks e-Power VL 2025 · Cash · Tanda jadi Rp 5.000.000 (Transfer)',
                'status' => PurchaseRequest::STATUS_APPROVED,
                'admin_note' => null,
                'route' => 'admin.purchase-requests.show',
                'params' => [$this->cash->id],
                'record' => ['type' => 'PurchaseRequest', 'id' => $this->cash->id],
                'anchor' => 'bukti-pembayaran',
            ], $notification->data);
        }

        foreach ([$mechanic, $inactiveAdmin, $this->customer] as $user) {
            $this->assertSame(0, $user->notifications()->count());
        }

        // Dibuka: langsung ke bagian bukti; daftar menampilkan status pengajuan & pembayaran terkini.
        $this->actingAs($this->admin)->get(route('admin.notifications.index'))
            ->assertSee('Bukti Pembayaran Baru · Fadlan')
            ->assertSee('Belum dibayar');
        $this->get(route('admin.notifications.open', $this->admin->notifications()->sole()->id))
            ->assertRedirect(route('admin.purchase-requests.show', $this->cash).'#bukti-pembayaran');
    }

    public function test_bukti_diterima_sebagian_memberi_tahu_sisa_pembayaran(): void
    {
        $proof = $this->proof(['amount' => 5_000_000]);

        $this->actingAs($this->admin)->patch(route('admin.payment-proofs.approve', [$this->cash, $proof]));

        $notification = $this->customer->notifications()->sole();
        $this->assertSame(PaymentProofReviewed::class, $notification->type);
        $this->assertSame([
            'title' => 'Pembayaran Diterima',
            'message' => 'Nissan Kicks e-Power VL 2025 · Cash · Tanda jadi Rp 5.000.000 (Transfer) diterima. Sisa Rp 500.000.000.',
            'status' => 'processing',
            'status_label' => 'Dibayar sebagian',
            'admin_note' => null,
            'route' => 'account.purchase-requests.index',
            'anchor' => "pengajuan-{$this->cash->id}",
        ], $notification->data);
        $this->assertSame(0, $this->admin->notifications()->count(), 'Sesama admin tidak diberi tahu.');

        $this->actingAs($this->customer)->get(route('account.notifications.index'))
            ->assertSee('Pembayaran Diterima')
            ->assertSee('Dibayar sebagian');
        $this->get(route('account.notifications.open', $notification->id))
            ->assertRedirect(route('account.purchase-requests.index')."#pengajuan-{$this->cash->id}");
    }

    public function test_dp_kredit_diterima_penuh_ditandai_dp_lunas(): void
    {
        $credit = PurchaseRequest::factory()->recycle($this->customer)->create([
            'payment_method' => 'credit', 'car_price' => 125_000_000, 'down_payment' => 63_000_000,
            'status' => PurchaseRequest::STATUS_APPROVED,
        ]);
        $proof = $this->proof(['type' => PaymentProof::TYPE_DOWN_PAYMENT, 'amount' => 63_000_000], $credit);

        $this->actingAs($this->admin)->patch(route('admin.payment-proofs.approve', [$credit, $proof]));

        $data = $this->customer->notifications()->sole()->data;
        $this->assertStringEndsWith('· Kredit · DP Rp 63.000.000 (Transfer) diterima. DP lunas.', $data['message']);
        $this->assertSame('completed', $data['status']);
        $this->assertSame('DP lunas', $data['status_label']);
    }

    public function test_bukti_ditolak_memberi_tahu_alasan(): void
    {
        $proof = $this->proof(['amount' => 1_000_000]);

        $this->actingAs($this->admin)->patch(route('admin.payment-proofs.reject', [$this->cash, $proof]), ['admin_note' => 'Nominal tidak sesuai.']);

        $data = $this->customer->notifications()->sole()->data;
        $this->assertSame('Bukti Pembayaran Ditolak', $data['title']);
        $this->assertSame('Nissan Kicks e-Power VL 2025 · Cash · Tanda jadi Rp 1.000.000 (Transfer)', $data['message']);
        $this->assertSame('rejected', $data['status']);
        $this->assertSame('Nominal tidak sesuai.', $data['admin_note']);

        $this->actingAs($this->customer)->get(route('account.notifications.index'))
            ->assertSee('Bukti Pembayaran Ditolak')
            ->assertSee('Nominal tidak sesuai.');
    }

    public function test_pembayaran_showroom_memberi_tahu_customer(): void
    {
        $this->actingAs($this->admin)->post(route('admin.payment-proofs.store', $this->cash), [
            'type' => PaymentProof::TYPE_SETTLEMENT, 'method' => PaymentProof::METHOD_CASH,
            'amount' => '505.000.000', 'paid_on' => '2026-10-12',
        ])->assertSessionHasNoErrors();

        $data = $this->customer->notifications()->sole()->data;
        $this->assertSame('Pembayaran Diterima', $data['title']);
        $this->assertSame('Nissan Kicks e-Power VL 2025 · Cash · Pelunasan Rp 505.000.000 (Tunai di showroom) diterima. Lunas.', $data['message']);
        $this->assertSame('Lunas', $data['status_label']);
        $this->assertSame(0, $this->admin->notifications()->count() + $this->owner->notifications()->count());
    }

    public function test_aksi_yang_ditolak_sistem_tidak_mengirim_notifikasi(): void
    {
        $proof = $this->proof();
        $this->actingAs($this->admin);

        // Alasan kosong, lalu bukti yang sudah diputuskan diproses lagi.
        $this->patch(route('admin.payment-proofs.reject', [$this->cash, $proof]), ['admin_note' => '']);
        $this->assertSame(0, $this->customer->notifications()->count());

        $this->patch(route('admin.payment-proofs.approve', [$this->cash, $proof]));
        $this->patch(route('admin.payment-proofs.approve', [$this->cash, $proof]));
        $this->patch(route('admin.payment-proofs.reject', [$this->cash, $proof]), ['admin_note' => 'Sudah diputuskan.']);
        $this->assertSame(1, $this->customer->notifications()->count());

        // Melebihi tagihan: ditolak sistem.
        $tooMuch = $this->proof(['amount' => 505_000_000]);
        $this->patch(route('admin.payment-proofs.approve', [$this->cash, $tooMuch]))->assertSessionHas('error');
        $this->assertSame(1, $this->customer->notifications()->count());
    }

    public function test_email_memuat_alasan_penolakan(): void
    {
        $proof = $this->proof(['status' => PaymentProof::STATUS_REJECTED, 'admin_note' => 'Foto bukti buram.']);
        $notification = new PaymentProofReviewed($proof, $this->cash);
        $notification->id = 'abc';

        $mail = $notification->toMail($this->customer);

        $this->assertSame('Bukti Pembayaran Ditolak | '.config('dealer.name'), $mail->subject);
        $this->assertContains('Catatan dealer: Foto bukti buram.', $mail->introLines);
    }
}
