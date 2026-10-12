<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\PurchaseRequestPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Langkah setelah pengajuan pembelian: kotak langkah di form, "Langkah berikutnya" di Pengajuan Saya, dan PDF.
 */
class PurchaseNextStepTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 10:00:00', 'Asia/Jakarta'));
        config([
            'dealer.name' => 'JAF Dealer', 'dealer.whatsapp' => '6281200001111',
            'dealer.address' => 'BSD Junction, Serpong', 'dealer.hours' => 'Senin-Sabtu 08.00-17.00 WIB',
        ]);
        $this->customer = User::factory()->create(['phone' => '081211112222']);
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Kicks e-Power VL', 'year' => 2025, 'price' => 520_000_000, 'stock' => 3, 'is_active' => true,
        ]);
    }

    private function purchase(string $status, string $method = 'cash'): PurchaseRequest
    {
        $factory = PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car);

        return ($method === 'credit' ? $factory->credit() : $factory)->create(['status' => $status]);
    }

    /**
     * @return array<string, array{string, string, ?string}>
     */
    public static function steps(): array
    {
        return [
            'menunggu cash' => ['pending', 'cash', 'menghubungi Anda lewat WhatsApp dalam 1 hari kerja'],
            'menunggu kredit' => ['pending', 'credit', 'menghubungi Anda lewat WhatsApp dalam 1 hari kerja'],
            'diproses cash' => ['processing', 'cash', 'Atur jadwal cek unit di showroom'],
            'diproses kredit' => ['processing', 'credit', 'Siapkan KTP, KK, NPWP'],
            'disetujui cash' => ['approved', 'cash', 'Lakukan pelunasan di showroom'],
            'disetujui kredit' => ['approved', 'credit', 'Bayar DP dan tanda tangani perjanjian kredit'],
            'selesai' => ['completed', 'cash', null],
            'ditolak' => ['rejected', 'credit', null],
            'dibatalkan' => ['cancelled', 'cash', null],
        ];
    }

    #[DataProvider('steps')]
    public function test_langkah_berikutnya_sesuai_status_dan_metode(string $status, string $method, ?string $expected): void
    {
        $purchase = $this->purchase($status, $method);
        $page = $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))->assertOk();
        $html = app(PurchaseRequestPdf::class)->html($purchase);

        if ($expected === null) {
            $this->assertNull($purchase->nextStep());
            $page->assertDontSee('Langkah berikutnya:')->assertDontSee('Tanya via WhatsApp');
            $this->assertStringNotContainsString('Langkah Selanjutnya', $html);

            return;
        }

        $this->assertStringContainsString($expected, (string) $purchase->nextStep());
        $page->assertSee('Langkah berikutnya:')->assertSee($expected);
        $this->assertStringContainsString('Langkah Selanjutnya', $html);
        $this->assertStringContainsString($expected, $html);
    }

    public function test_tombol_whatsapp_berisi_nomor_dokumen_dan_hanya_jika_nomor_diisi(): void
    {
        $purchase = $this->purchase('processing', 'credit');
        $text = rawurlencode('Halo JAF Dealer, saya ingin menanyakan pengajuan '.$purchase->documentNumber().' (Nissan Kicks e-Power VL 2025, kredit).');

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertSee('https://wa.me/6281200001111?text='.$text, false)
            ->assertSee('Tanya via WhatsApp');

        config(['dealer.whatsapp' => '']);
        $this->get(route('account.purchase-requests.index'))->assertDontSee('Tanya via WhatsApp');
    }

    public function test_form_menampilkan_langkah_dan_peringatan_transfer(): void
    {
        $this->actingAs($this->customer)->get(route('purchase-requests.create', $this->car))
            ->assertOk()
            ->assertSee('Setelah Anda mengirim pengajuan')
            ->assertSee('Lihat dan coba mobilnya di showroom kami: BSD Junction, Serpong, buka Senin-Sabtu 08.00-17.00 WIB.')
            ->assertSee('data-step-method="cash"', false)
            ->assertSee('data-step-method="credit"', false)
            ->assertSee(PurchaseRequest::SAFE_PAYMENT_NOTE);

        config(['dealer.address' => '', 'dealer.hours' => '']);
        $this->get(route('purchase-requests.create', $this->car))->assertSee('Lihat dan coba mobilnya di showroom kami.');
    }

    public function test_pdf_memuat_peringatan_transfer(): void
    {
        $html = app(PurchaseRequestPdf::class)->html($this->purchase('completed'));

        $this->assertStringContainsString(e(PurchaseRequest::SAFE_PAYMENT_NOTE), $html);
    }
}
