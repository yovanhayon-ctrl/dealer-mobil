<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PaymentProof;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Batas pembayaran pertama setelah pengajuan disetujui (DEALER_PAYMENT_DUE_DAYS, bawaan 3 hari).
 */
class PaymentDeadlineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        config(['dealer.payment_due_days' => 3]);
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create(['name' => 'Fadlan']);
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Magnite Premium CVT', 'year' => 2025, 'stock' => 4,
        ]);
    }

    private function approved(array $attributes = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->status('approved')->create([
            'payment_method' => 'cash', 'car_price' => 285_000_000, ...$attributes,
        ]);
    }

    private function overdueIds(): array
    {
        return PurchaseRequest::query()->paymentOverdue()->pluck('id')->all();
    }

    public function test_menyetujui_mencatat_waktu_disetujui(): void
    {
        $purchase = PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->status('processing')->create();

        $this->actingAs($this->admin)->patch(route('admin.purchase-requests.update-status', $purchase), ['status' => 'approved'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($purchase->fresh()->approved_at->equalTo(now()));
        $this->assertSame('2026-10-18 23:59:59', $purchase->fresh()->paymentDueAt()->format('Y-m-d H:i:s'));
    }

    public function test_lewat_tenggat_mulai_hari_setelah_batas_dan_scope_sama_dengan_model(): void
    {
        $purchase = $this->approved();

        $this->travelTo(Carbon::parse('2026-10-18 23:59:00', 'Asia/Jakarta'));
        $this->assertFalse($purchase->fresh()->isPaymentOverdue());
        $this->assertSame([], $this->overdueIds());

        $this->travelTo(Carbon::parse('2026-10-19 00:01:00', 'Asia/Jakarta'));
        $this->assertTrue($purchase->fresh()->isPaymentOverdue());
        $this->assertSame([$purchase->id], $this->overdueIds());

        $this->travelTo(Carbon::parse('2026-10-21 09:00:00', 'Asia/Jakarta'));
        $this->assertSame(3, $purchase->fresh()->paymentOverdueDays());
    }

    public function test_lama_tenggat_mengikuti_konfigurasi(): void
    {
        config(['dealer.payment_due_days' => 5]);
        $purchase = $this->approved();

        $this->travelTo(Carbon::parse('2026-10-20 12:00:00', 'Asia/Jakarta'));
        $this->assertFalse($purchase->fresh()->isPaymentOverdue());

        $this->travelTo(Carbon::parse('2026-10-21 08:00:00', 'Asia/Jakarta'));
        $this->assertTrue($purchase->fresh()->isPaymentOverdue());
    }

    public function test_bukti_menunggu_atau_pembayaran_diterima_menghentikan_tenggat(): void
    {
        $pending = $this->approved();
        PaymentProof::factory()->recycle($pending)->create(['purchase_request_id' => $pending->id]);
        $partial = $this->approved();
        PaymentProof::factory()->recycle($partial)->approved()->create(['purchase_request_id' => $partial->id, 'amount' => 10_000_000]);
        $rejectedOnly = $this->approved();
        PaymentProof::factory()->recycle($rejectedOnly)->rejected()->create(['purchase_request_id' => $rejectedOnly->id]);
        $noDownPayment = $this->approved(['payment_method' => 'credit', 'down_payment' => 0]);
        $processing = PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->status('processing')->create();

        $this->travelTo(Carbon::parse('2026-10-25 10:00:00', 'Asia/Jakarta'));

        $this->assertSame([$rejectedOnly->id], $this->overdueIds());
        foreach ([$pending, $partial, $noDownPayment, $processing] as $purchase) {
            $this->assertFalse($purchase->fresh()->isPaymentOverdue(), "Pengajuan #{$purchase->id}");
        }
        $this->assertTrue($rejectedOnly->fresh()->isPaymentOverdue());
    }

    public function test_admin_melihat_filter_badge_peringatan_dan_catatan_pembatalan(): void
    {
        $overdue = $this->approved();
        $onTime = PurchaseRequest::factory()->recycle($this->customer)->create(['payment_method' => 'cash', 'car_price' => 100_000_000]);
        $this->travelTo(Carbon::parse('2026-10-20 09:00:00', 'Asia/Jakarta'));
        $onTime->update(['status' => 'approved', 'approved_at' => now()]);

        $this->actingAs($this->admin)->get(route('admin.purchase-requests.index', ['pembayaran' => 'lewat-tenggat']))
            ->assertOk()
            ->assertViewHas('purchaseRequests', fn ($list) => $list->pluck('id')->all() === [$overdue->id])
            ->assertSee('badge-status-red ms-1">Lewat tenggat</span>', false);

        $this->get(route('admin.purchase-requests.show', $overdue))
            ->assertSee('Lewat batas pembayaran pertama.')
            ->assertSee('Minggu, 18 Okt 2026')
            ->assertSee('(2 hari lalu)')
            ->assertSee(PurchaseRequest::PAYMENT_OVERDUE_NOTE, false);
        $this->get(route('admin.purchase-requests.show', $onTime))
            ->assertDontSee('Lewat batas pembayaran pertama.')
            ->assertDontSee(PurchaseRequest::PAYMENT_OVERDUE_NOTE, false);

        // Dibatalkan admin: unit kembali ke stok dan tidak lagi lewat tenggat.
        $stock = $this->car->fresh()->stock;
        $this->patch(route('admin.purchase-requests.update-status', $overdue), ['status' => 'cancelled', 'admin_note' => PurchaseRequest::PAYMENT_OVERDUE_NOTE])
            ->assertSessionHasNoErrors();
        $this->assertSame($stock + 1, $this->car->fresh()->stock);
        $this->assertSame([], $this->overdueIds());
    }

    public function test_dashboard_menampilkan_jumlah_lewat_tenggat(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertDontSee('class="dashboard-attention', false);

        $this->approved();
        $this->travelTo(Carbon::parse('2026-10-20 09:00:00', 'Asia/Jakarta'));

        $this->get(route('admin.dashboard'))
            ->assertSee('class="dashboard-attention', false)
            ->assertSee('<strong>1</strong> pengajuan lewat tenggat bayar', false)
            ->assertSee(route('admin.purchase-requests.index', ['pembayaran' => 'lewat-tenggat']), false);
    }

    public function test_customer_melihat_batas_bayar_lalu_peringatan(): void
    {
        $this->approved();

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertSee('Lakukan pembayaran pertama paling lambat')
            ->assertSee('Minggu, 18 Okt 2026')
            ->assertDontSee('Batas pembayaran sudah lewat');

        $this->travelTo(Carbon::parse('2026-10-19 09:00:00', 'Asia/Jakarta'));

        $this->get(route('account.purchase-requests.index'))
            ->assertSee('Batas pembayaran sudah lewat (Minggu, 18 Okt 2026).')
            ->assertDontSee('Lakukan pembayaran pertama paling lambat');
    }

    public function test_customer_tanpa_batas_bayar_saat_bukti_sedang_diverifikasi(): void
    {
        $purchase = $this->approved();
        PaymentProof::factory()->recycle($purchase)->create(['purchase_request_id' => $purchase->id]);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertDontSee('Lakukan pembayaran pertama paling lambat')
            ->assertDontSee('Batas pembayaran sudah lewat');
    }
}
