<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PaymentProof;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\PaymentDueReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pengingat H-1 batas pembayaran pertama (bagian dari reminders:send & tombol dashboard).
 */
class PaymentDueReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        // Selasa, 20 Okt 2026, tenggat 3 hari: disetujui 18 Okt => batas 21 Okt (besok).
        $this->travelTo(Carbon::parse('2026-10-20 16:00:00', 'Asia/Jakarta'));
        config(['dealer.payment_due_days' => 3]);
        config(['dealer.bank' => ['name' => 'BCA', 'account' => '1234567890', 'holder' => 'PT JAF Dealer']]);
        $this->customer = User::factory()->create(['name' => 'Yoseph']);
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id, 'name' => 'Livina VL CVT', 'year' => 2025,
        ]);
    }

    private function approved(string $approvedAt, array $attributes = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->status('approved')->create([
            'payment_method' => 'cash', 'car_price' => 330_000_000, 'phone' => '082147304198', 'approved_at' => $approvedAt, ...$attributes,
        ]);
    }

    public function test_hanya_batas_besok_yang_belum_dibayar_diingatkan_sekali(): void
    {
        $dueTomorrow = $this->approved('2026-10-18 09:00:00');
        $dueLater = $this->approved('2026-10-19 09:00:00');
        $overdue = $this->approved('2026-10-15 09:00:00');
        $paid = $this->approved('2026-10-18 10:00:00');
        PaymentProof::factory()->recycle($paid)->approved()->create(['purchase_request_id' => $paid->id]);
        $proofWaiting = $this->approved('2026-10-18 11:00:00');
        PaymentProof::factory()->recycle($proofWaiting)->create(['purchase_request_id' => $proofWaiting->id]);
        $processing = PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->status('processing')->create();

        $this->artisan('reminders:send')
            ->expectsOutput('Pengingat terkirim: 0 test drive, 0 servis, 0 ke mekanik, 1 pembayaran.')
            ->assertSuccessful();

        $notification = $this->customer->notifications()->sole();
        $this->assertSame(PaymentDueReminder::class, $notification->type);
        $this->assertSame('Pengingat: Batas Pembayaran Besok', $notification->data['title']);
        $this->assertSame('Nissan Livina VL CVT 2025 · Cash. Lakukan pembayaran pertama (tanda jadi, DP, atau pelunasan) paling lambat Rabu, 21 Okt 2026.', $notification->data['message']);
        $this->assertSame('Transfer ke BCA 1234567890 a.n. PT JAF Dealer atau bayar langsung di showroom, lalu kirim bukti transfer di menu Pengajuan Saya.', $notification->data['admin_note']);
        $this->assertSame("pengajuan-{$dueTomorrow->id}", $notification->data['anchor']);

        $this->assertNotNull($dueTomorrow->fresh()->payment_reminded_at);
        foreach ([$dueLater, $overdue, $paid, $proofWaiting, $processing] as $purchase) {
            $this->assertNull($purchase->fresh()->payment_reminded_at, "Pengajuan #{$purchase->id}");
        }

        $this->artisan('reminders:send')->expectsOutput('Pengingat terkirim: 0 test drive, 0 servis, 0 ke mekanik, 0 pembayaran.');
        $this->assertSame(1, $this->customer->notifications()->count());
    }

    public function test_mengikuti_lama_tenggat_dan_rekening_kosong(): void
    {
        config(['dealer.payment_due_days' => 1, 'dealer.bank' => ['name' => '', 'account' => '', 'holder' => '']]);
        // Tenggat 1 hari: disetujui hari ini => batas besok.
        $purchase = $this->approved('2026-10-20 09:00:00');

        $this->artisan('reminders:send --dry-run')
            ->expectsOutput("Pembayaran pengajuan #{$purchase->id} · Yoseph · batas 21 Okt 2026")
            ->expectsOutput('Akan diingatkan: 0 test drive, 0 servis, 1 pembayaran (belum dikirim).');
        $this->assertSame(0, $this->customer->notifications()->count());

        $this->artisan('reminders:send');
        $this->assertStringStartsWith('Transfer ke rekening resmi dealer atau bayar langsung di showroom', $this->customer->notifications()->sole()->data['admin_note']);
    }

    public function test_tombol_dashboard_ikut_mengirim_pengingat_pembayaran(): void
    {
        $this->approved('2026-10-18 09:00:00');

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.reminders.send'))
            ->assertSessionHas('success', 'Pengingat terkirim: 0 test drive, 0 servis, dan 1 pembayaran.');
        $this->assertSame(1, $this->customer->notifications()->count());
    }

    public function test_detail_pengajuan_menampilkan_batas_pengingat_dan_whatsapp(): void
    {
        $purchase = $this->approved('2026-10-18 09:00:00', ['payment_reminded_at' => now()]);

        $html = $this->actingAs(User::factory()->sales()->create())->get(route('admin.purchase-requests.show', $purchase))
            ->assertSee('Batas pembayaran pertama')
            ->assertSee('Rabu, 21 Okt 2026')
            ->assertSee('pengingat terkirim 20 Okt 2026 16:00')
            ->assertSee('Ingatkan via WhatsApp')
            ->getContent();
        $this->assertStringContainsString('https://wa.me/6282147304198?text=', $html);
        $this->assertStringContainsString(rawurlencode('mengingatkan pembayaran pertama untuk pengajuan Nissan Livina VL CVT 2025'), $html);

        // Customer Service hanya melihat pengajuan: tanpa tombol WhatsApp pengingat.
        $this->actingAs(User::factory()->customerService()->create())->get(route('admin.purchase-requests.show', $purchase))
            ->assertSee('Batas pembayaran pertama')
            ->assertDontSee('Ingatkan via WhatsApp');

        // Sudah dibayar: tidak ada batas & tombol.
        PaymentProof::factory()->recycle($purchase)->approved()->create(['purchase_request_id' => $purchase->id]);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.purchase-requests.show', $purchase))
            ->assertDontSee('Batas pembayaran pertama');
    }
}
