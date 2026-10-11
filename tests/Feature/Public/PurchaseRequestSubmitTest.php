<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\PurchaseRequestController;
use App\Http\Requests\PurchaseRequestRequest;
use App\Models\Brand;
use App\Models\Car;
use App\Models\Promo;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\CreditCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Form & penyimpanan pengajuan pembelian customer (/mobil/{slug}/ajukan).
 */
class PurchaseRequestSubmitTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 10:00:00', 'Asia/Jakarta'));
        $this->customer = User::factory()->create(['name' => 'Budi Santoso', 'phone' => '081211112222']);
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Kicks e-Power VL', 'year' => 2025, 'slug' => 'nissan-kicks-e-power-vl-2025',
            'price' => 520_000_000, 'stock' => 3, 'is_active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'payment_method' => 'credit',
            'down_payment' => '104.000.000',
            'tenor_months' => '36',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 10, Bandung',
            'notes' => 'Warna putih.',
            ...$overrides,
        ];
    }

    private function submit(array $overrides = [], ?User $user = null, ?Car $car = null): TestResponse
    {
        $car ??= $this->car;

        return $this->actingAs($user ?? $this->customer)
            ->from(route('purchase-requests.create', $car))
            ->post(route('purchase-requests.store', $car), $this->payload($overrides));
    }

    private function activePromo(int $discount): Promo
    {
        return Promo::factory()->create([
            'car_id' => $this->car->id, 'discount_amount' => $discount, 'is_active' => true,
            'start_date' => '2026-10-01', 'end_date' => '2026-10-31',
        ]);
    }

    // ---------- Akses & form ----------

    public function test_tamu_diarahkan_ke_login_lalu_kembali_ke_form(): void
    {
        // Laravel menyimpan URL tujuan dengan query string terurut alfabetis.
        $url = route('purchase-requests.create', ['car' => $this->car, 'dp' => 200000000, 'metode' => 'kredit', 'tenor' => 48]);

        $this->get($url)->assertRedirect(route('login'));
        $this->post(route('purchase-requests.store', $this->car), $this->payload())->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $this->customer->email, 'password' => 'password'])
            ->assertRedirect($url);
        $this->assertSame(0, PurchaseRequest::count());
    }

    public function test_admin_melihat_pesan_dan_tidak_bisa_mengajukan(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('purchase-requests.create', $this->car))
            ->assertOk()
            ->assertSee(PurchaseRequestRequest::ADMIN_MESSAGE)
            ->assertDontSee('Kirim Pengajuan');

        $this->submit(user: $admin)
            ->assertRedirect(route('purchase-requests.create', $this->car))
            ->assertSessionHas('error', PurchaseRequestRequest::ADMIN_MESSAGE);
        $this->assertSame(0, PurchaseRequest::count());
    }

    public function test_form_menampilkan_ringkasan_harga_promo_dan_nilai_awal(): void
    {
        $this->activePromo(15_000_000);

        $this->actingAs($this->customer)->get(route('purchase-requests.create', $this->car))
            ->assertOk()
            ->assertSee('<title>Ajukan Pembelian Nissan Kicks e-Power VL 2025 | ', false)
            ->assertSeeInOrder(['Rp 520.000.000', 'Rp 505.000.000'])
            ->assertSee('data-price="505000000"', false)
            // Default cash, DP minimal 20% dari harga promo, tenor 36.
            ->assertSee('id="payment_method_cash" value="cash" checked', false)
            ->assertSee('value="101.000.000"', false)
            ->assertSee('<option value="36" selected>', false)
            ->assertSee('value="081211112222"', false)
            ->assertSee(asset('js/purchase-request.js'), false);
    }

    public function test_nilai_dari_simulasi_terbawa_ke_form(): void
    {
        $this->actingAs($this->customer)
            ->get(route('purchase-requests.create', ['car' => $this->car, 'metode' => 'kredit', 'dp' => '200000000', 'tenor' => 48]))
            ->assertSee('id="payment_method_credit" value="credit" checked', false)
            ->assertSee('value="200.000.000"', false)
            ->assertSee('<option value="48" selected>', false);

        // DP di luar batas atau tenor tidak dikenal kembali ke nilai default.
        $this->get(route('purchase-requests.create', ['car' => $this->car, 'dp' => '1000', 'tenor' => 18]))
            ->assertSee('value="104.000.000"', false)
            ->assertSee('<option value="36" selected>', false);
    }

    public function test_mobil_nonaktif_404_dan_stok_habis_tanpa_form(): void
    {
        $this->car->update(['stock' => 0]);
        $this->actingAs($this->customer)->get(route('purchase-requests.create', $this->car))
            ->assertOk()
            ->assertSee('Stok mobil ini habis, pengajuan belum bisa dilakukan.')
            ->assertDontSee('Kirim Pengajuan');
        $this->submit()->assertSessionHasErrors(['car' => 'Stok mobil ini habis, pengajuan belum bisa dilakukan.']);

        $this->car->update(['stock' => 2, 'is_active' => false]);
        $this->get(route('purchase-requests.create', $this->car))->assertNotFound();
        $this->get('/mobil/tidak-ada/ajukan')->assertNotFound();
        $this->assertSame(0, PurchaseRequest::count());
    }

    // ---------- Validasi ----------

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'metode kosong' => [['payment_method' => ''], 'payment_method'],
            'metode tidak dikenal' => [['payment_method' => 'leasing'], 'payment_method'],
            'kredit tanpa dp' => [['down_payment' => ''], 'down_payment'],
            'kredit dp di bawah 20%' => [['down_payment' => '103.999.999'], 'down_payment'],
            'kredit dp di atas 90%' => [['down_payment' => '468.000.001'], 'down_payment'],
            'kredit tanpa tenor' => [['tenor_months' => ''], 'tenor_months'],
            'tenor tidak tersedia' => [['tenor_months' => '18'], 'tenor_months'],
            'nomor tidak valid' => [['phone' => '021555'], 'phone'],
            'alamat kosong' => [['address' => '   '], 'address'],
            'alamat terlalu pendek' => [['address' => 'Bandung'], 'address'],
            'alamat terlalu panjang' => [['address' => str_repeat('a', 501)], 'address'],
            'catatan terlalu panjang' => [['notes' => str_repeat('a', 501)], 'notes'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_validasi_menolak_isian_tidak_valid(array $overrides, string $field): void
    {
        $this->submit($overrides)
            ->assertRedirect(route('purchase-requests.create', $this->car))
            ->assertSessionHasErrors($field);

        $this->assertSame(0, PurchaseRequest::count());
    }

    public function test_pesan_batas_dp_memakai_harga_promo(): void
    {
        $this->activePromo(20_000_000);

        $this->submit(['down_payment' => '99.999.999'])
            ->assertSessionHasErrors(['down_payment' => 'Uang muka harus antara Rp 100.000.000 (20%) dan Rp 450.000.000 (90%) dari harga.']);
    }

    // ---------- Tersimpan ----------

    public function test_kredit_tersimpan_dengan_nilai_dihitung_ulang_server(): void
    {
        $this->activePromo(15_000_000);
        $expected = app(CreditCalculator::class)->calculate(505_000_000, 104_000_000, 36);

        $this->submit()
            ->assertRedirect(route('account.purchase-requests.index'))
            ->assertSessionHas('success', 'Pengajuan pembelian Nissan Kicks e-Power VL 2025 (kredit DP Rp 104.000.000, 36 bulan, cicilan Rp '
                .number_format($expected['monthly_installment'], 0, ',', '.').'/bulan) berhasil dikirim. Tim kami akan menghubungi Anda.');

        $purchase = PurchaseRequest::sole();
        $this->assertSame($this->customer->id, $purchase->user_id);
        $this->assertSame(505_000_000, $purchase->car_price);
        $this->assertSame('credit', $purchase->payment_method);
        $this->assertSame(104_000_000, $purchase->down_payment);
        $this->assertSame(36, $purchase->tenor_months);
        $this->assertEquals(6, (float) $purchase->interest_rate);
        $this->assertSame($expected['monthly_installment'], $purchase->monthly_installment);
        $this->assertSame('081234567890', $purchase->phone);
        $this->assertSame('Jl. Merdeka No. 10, Bandung', $purchase->address);
        $this->assertSame('Warna putih.', $purchase->notes);
        $this->assertSame('pending', $purchase->status);

        // Stok baru berubah saat admin menyetujui.
        $this->assertSame(3, $this->car->fresh()->stock);
    }

    public function test_nilai_kredit_dari_browser_diabaikan(): void
    {
        $this->submit(['car_price' => 1, 'monthly_installment' => 1, 'interest_rate' => 0, 'status' => 'approved', 'user_id' => 999])
            ->assertSessionHasNoErrors();

        $purchase = PurchaseRequest::sole();
        $this->assertSame(520_000_000, $purchase->car_price);
        $this->assertGreaterThan(1, $purchase->monthly_installment);
        $this->assertSame('pending', $purchase->status);
        $this->assertSame($this->customer->id, $purchase->user_id);
    }

    public function test_cash_mengosongkan_kolom_kredit(): void
    {
        $this->submit(['payment_method' => 'cash', 'down_payment' => '1', 'tenor_months' => '18', 'notes' => '  '])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($message) => str_contains($message, '(cash Rp 520.000.000)'));

        $purchase = PurchaseRequest::sole();
        $this->assertSame('cash', $purchase->payment_method);
        $this->assertSame(520_000_000, $purchase->car_price);
        $this->assertNull($purchase->down_payment);
        $this->assertNull($purchase->tenor_months);
        $this->assertNull($purchase->interest_rate);
        $this->assertNull($purchase->monthly_installment);
        $this->assertNull($purchase->notes);
    }

    // ---------- Pengajuan ganda ----------

    /**
     * @return array<string, array{string}>
     */
    public static function activeStatuses(): array
    {
        return array_combine(PurchaseRequest::ACTIVE_STATUSES, array_map(fn ($status) => [$status], PurchaseRequest::ACTIVE_STATUSES));
    }

    #[DataProvider('activeStatuses')]
    public function test_pengajuan_aktif_untuk_mobil_yang_sama_ditolak(string $status): void
    {
        PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->status($status)->create();

        $this->actingAs($this->customer)->get(route('purchase-requests.create', $this->car))
            ->assertSee('Anda sudah punya pengajuan untuk mobil ini ('.PurchaseRequest::STATUS_LABELS[$status])
            ->assertDontSee('Kirim Pengajuan');

        $this->submit()->assertSessionHasErrors('car');
        $this->assertSame(1, PurchaseRequest::count());
    }

    public function test_boleh_mengajukan_lagi_setelah_status_akhir_dan_customer_lain_tidak_terpengaruh(): void
    {
        foreach (['rejected', 'cancelled', 'completed'] as $status) {
            PurchaseRequest::factory()->recycle($this->customer)->recycle($this->car)->status($status)->create();
        }
        PurchaseRequest::factory()->recycle($this->car)->create();

        $this->submit()->assertSessionHasNoErrors();
        $this->assertSame(1, $this->customer->purchaseRequests()->where('status', 'pending')->count());
    }

    // ---------- Rate limit & tombol ----------

    public function test_rate_limit_pengajuan_per_user(): void
    {
        foreach (range(1, PurchaseRequestController::MAX_SUBMISSIONS_PER_MINUTE) as $i) {
            $this->submit(['payment_method' => ''])->assertSessionHasErrors('payment_method');
        }

        $this->submit()
            ->assertRedirect(route('purchase-requests.create', $this->car))
            ->assertSessionHas('error', 'Terlalu banyak percobaan pengajuan. Coba lagi dalam 1 menit.');
        $this->assertSame(0, PurchaseRequest::count());

        $this->travel(61)->seconds();
        $this->submit()->assertSessionHasNoErrors();
    }

    public function test_tombol_ajukan_di_simulasi_dan_promo(): void
    {
        $promo = $this->activePromo(15_000_000);

        $this->get(route('credit.index', ['mobil' => $this->car->slug, 'dp' => '200.000.000', 'tenor' => 48]))
            ->assertSee('Ajukan dengan Simulasi Ini')
            ->assertSee('href="'.e(route('purchase-requests.create', ['car' => $this->car, 'metode' => 'kredit', 'dp' => 200000000, 'tenor' => 48])).'"', false);

        $this->get(route('promos.show', $promo))
            ->assertSee('href="'.route('purchase-requests.create', $this->car).'"', false);

        // Stok habis: tombol ajukan tidak ditawarkan.
        $this->car->update(['stock' => 0]);
        $this->get(route('credit.index', ['mobil' => $this->car->slug]))->assertDontSee('Ajukan dengan Simulasi Ini');
    }

    public function test_pengajuan_baru_tampil_di_admin(): void
    {
        $this->submit()->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.purchase-requests.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Kicks e-Power VL')
            ->assertSee('Menunggu');
    }
}
