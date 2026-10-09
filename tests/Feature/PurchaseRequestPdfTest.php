<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\PurchaseRequestPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Bukti pengajuan pembelian (PDF) untuk customer pemilik & admin.
 */
class PurchaseRequestPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private PurchaseRequest $credit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 10:00:00', 'Asia/Jakarta'));
        config(['dealer.name' => 'JAF Dealer', 'dealer.phone' => '021-555-0101', 'dealer.seed.customer_password' => 'rahasia-seed']);
        $this->customer = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.test']);
        $car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Kicks e-Power VL', 'year' => 2025, 'vehicle_condition' => Car::CONDITION_NEW, 'color' => 'Putih',
        ]);
        $this->credit = PurchaseRequest::factory()->recycle($this->customer)->recycle($car)->credit()->create([
            'car_price' => 505_000_000,
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 10, Bandung',
            'notes' => 'Warna putih <b>tebal</b>.',
            'admin_note' => 'Dokumen lengkap.',
        ]);
    }

    private function html(PurchaseRequest $purchaseRequest): string
    {
        return app(PurchaseRequestPdf::class)->html($purchaseRequest);
    }

    public function test_customer_mengunduh_pdf_miliknya(): void
    {
        $response = $this->actingAs($this->customer)->get(route('account.purchase-requests.pdf', $this->credit))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame(
            'inline; filename="bukti-pengajuan-PB-2026-'.sprintf('%06d', $this->credit->id).'.pdf"',
            $response->headers->get('Content-Disposition'),
        );
    }

    public function test_milik_customer_lain_404_dan_tamu_ke_login(): void
    {
        $this->get(route('account.purchase-requests.pdf', $this->credit))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('account.purchase-requests.pdf', $this->credit))
            ->assertNotFound();
    }

    public function test_admin_mengunduh_semua_pengajuan_dan_customer_tidak_bisa_route_admin(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.purchase-requests.pdf', $this->credit))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->actingAs($this->customer)->get(route('admin.purchase-requests.pdf', $this->credit))->assertForbidden();
    }

    public function test_isi_dokumen_kredit(): void
    {
        $credit = $this->credit;
        $html = $this->html($credit);

        $this->assertStringContainsString('Bukti Pengajuan Pembelian', $html);
        $this->assertStringContainsString($credit->documentNumber(), $html);
        $this->assertStringContainsString('JAF Dealer', $html);
        $this->assertStringContainsString('Telp. 021-555-0101', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('budi@example.test', $html);
        $this->assertStringContainsString('Jl. Merdeka No. 10, Bandung', $html);
        $this->assertStringContainsString('Nissan Kicks e-Power VL 2025', $html);
        $this->assertStringContainsString('Putih', $html);
        $this->assertStringContainsString('Kredit', $html);
        $this->assertStringContainsString('Rp '.number_format($credit->down_payment, 0, ',', '.'), $html);
        $this->assertStringContainsString("{$credit->tenor_months} bulan", $html);
        $this->assertStringContainsString('Rp '.number_format($credit->monthly_installment, 0, ',', '.'), $html);
        $this->assertStringContainsString('Rp '.number_format($credit->totalPayment(), 0, ',', '.'), $html);
        $this->assertStringContainsString('Dokumen lengkap.', $html);
        $this->assertStringContainsString('bukan bukti pembayaran', $html);

        // Escape & tidak ada sumber eksternal / data konfigurasi lain.
        $this->assertStringContainsString('&lt;b&gt;tebal&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>tebal</b>', $html);
        $this->assertStringNotContainsString('rahasia-seed', $html);
        $this->assertDoesNotMatchRegularExpression('/(src|href)=["\']https?:/i', $html);
    }

    public function test_isi_dokumen_cash_tanpa_rincian_kredit(): void
    {
        $cash = PurchaseRequest::factory()->recycle($this->customer)->status(PurchaseRequest::STATUS_COMPLETED)->create(['car_price' => 300_000_000]);

        $html = $this->html($cash);

        $this->assertStringContainsString('Cash', $html);
        $this->assertStringContainsString('Selesai', $html);
        $this->assertStringContainsString('Rp 300.000.000', $html);
        $this->assertStringNotContainsString('Cicilan per bulan', $html);
        $this->assertStringNotContainsString('Uang muka (DP)', $html);
    }

    public function test_tombol_cetak_di_pengajuan_saya_dan_detail_admin(): void
    {
        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertOk()
            ->assertSee('href="'.route('account.purchase-requests.pdf', $this->credit).'"', false);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.purchase-requests.show', $this->credit))
            ->assertOk()
            ->assertSee('href="'.route('admin.purchase-requests.pdf', $this->credit).'"', false);
    }

    public function test_nomor_dokumen(): void
    {
        $this->assertSame(sprintf('PB-2026-%06d', $this->credit->id), $this->credit->documentNumber());
    }
}
