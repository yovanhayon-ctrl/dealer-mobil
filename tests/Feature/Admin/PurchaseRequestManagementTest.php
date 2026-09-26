<?php

namespace Tests\Feature\Admin;

use App\Actions\ChangePurchaseRequestStatus;
use App\Models\Brand;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\CreditCalculator;
use Database\Seeders\CarSeeder;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\PromoSeeder;
use Database\Seeders\PurchaseRequestSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PurchaseRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-26 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.test']);
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Toyota'])->id,
            'name' => 'Avanza', 'year' => 2025, 'price' => 300_000_000, 'stock' => 3,
        ]);
    }

    private function purchase(array $attributes = [], ?Car $car = null): PurchaseRequest
    {
        return PurchaseRequest::factory()->recycle($this->customer)->recycle($car ?? $this->car)->create([
            'car_price' => 300_000_000,
            ...$attributes,
        ]);
    }

    private function changeStatus(PurchaseRequest $purchaseRequest, array $data)
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.purchase-requests.show', $purchaseRequest))
            ->patch(route('admin.purchase-requests.update-status', $purchaseRequest), $data);
    }

    private function stock(?Car $car = null): int
    {
        return ($car ?? $this->car)->fresh()->stock;
    }

    /**
     * @return array<int, int>
     */
    private function listedIds(array $query = []): array
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.purchase-requests.index', $query))
            ->assertOk()
            ->viewData('purchaseRequests')
            ->pluck('id')
            ->sort()
            ->values()
            ->all();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $purchase = $this->purchase();

        $this->get('/admin/pengajuan')->assertRedirect(route('login'));
        $this->get("/admin/pengajuan/{$purchase->id}")->assertRedirect(route('login'));
        $this->patch("/admin/pengajuan/{$purchase->id}/status", ['status' => 'processing'])->assertRedirect(route('login'));

        $this->assertSame('pending', $purchase->fresh()->status);
    }

    public function test_customer_mendapat_403(): void
    {
        $purchase = $this->purchase();

        $this->actingAs($this->customer);
        $this->get('/admin/pengajuan')->assertForbidden();
        $this->get("/admin/pengajuan/{$purchase->id}")->assertForbidden();
        $this->patch("/admin/pengajuan/{$purchase->id}/status", ['status' => 'processing'])->assertForbidden();

        $this->assertSame('pending', $purchase->fresh()->status);
    }

    public function test_admin_tidak_bisa_membuat_atau_menghapus(): void
    {
        $purchase = $this->purchase();

        $this->actingAs($this->admin);
        $this->post('/admin/pengajuan', [])->assertMethodNotAllowed();
        $this->delete("/admin/pengajuan/{$purchase->id}")->assertMethodNotAllowed();
        $this->put("/admin/pengajuan/{$purchase->id}", [])->assertMethodNotAllowed();
        $this->get('/admin/pengajuan/tambah')->assertNotFound();

        $this->assertModelExists($purchase);
    }

    public function test_daftar_menampilkan_data_dan_tanpa_n_plus_1(): void
    {
        $this->purchase()->forceFill(['payment_method' => 'cash'])->save();

        $this->actingAs($this->admin)->get(route('admin.purchase-requests.index'))
            ->assertOk()
            ->assertSeeInOrder(['26 Sep 2026', 'Budi Santoso', 'budi@example.test', 'Toyota Avanza 2025', 'Cash', 'Rp 300.000.000', 'Menunggu']);

        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->admin)->get(route('admin.purchase-requests.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $queriesWithOne = $count();
        PurchaseRequest::factory()->count(9)->create();
        $this->assertSame($queriesWithOne, $count());
    }

    public function test_filter_status_metode_dan_kata_kunci(): void
    {
        $siti = User::factory()->create(['name' => 'Siti Rahmawati', 'email' => 'siti@contoh.test']);
        $honda = Car::factory()->create(['brand_id' => Brand::factory()->create(['name' => 'Honda'])->id, 'name' => 'HR-V']);

        $cash = $this->purchase(['status' => 'pending']);
        $credit = PurchaseRequest::factory()->credit()->recycle($this->customer)->recycle($this->car)
            ->create(['car_price' => 300_000_000, 'status' => 'processing']);
        $other = PurchaseRequest::factory()->recycle($siti)->recycle($honda)->create(['status' => 'rejected']);

        $this->assertSame([$cash->id], $this->listedIds(['status' => 'pending']));
        $this->assertSame([$credit->id], $this->listedIds(['metode' => 'kredit']));
        $this->assertSame([$cash->id, $other->id], $this->listedIds(['metode' => 'cash']));
        $this->assertSame([$other->id], $this->listedIds(['q' => 'siti']));
        $this->assertSame([$other->id], $this->listedIds(['q' => 'contoh.test']));
        $this->assertSame([$other->id], $this->listedIds(['q' => 'honda']));
        $this->assertSame([$cash->id, $credit->id], $this->listedIds(['q' => 'avanza']));
        $this->assertSame([$cash->id, $credit->id, $other->id], $this->listedIds(['status' => 'x', 'metode' => 'transfer']));

        $this->actingAs($this->admin)->get(route('admin.purchase-requests.index', ['q' => 'tidak-ada']))
            ->assertSee('Pengajuan tidak ditemukan');
    }

    public function test_pagination_sepuluh_per_halaman(): void
    {
        PurchaseRequest::factory()->count(12)->create();

        $this->assertCount(10, $this->listedIds());
        $this->assertCount(2, $this->listedIds(['page' => 2]));
    }

    public function test_detail_kredit_menampilkan_rincian_dari_credit_calculator(): void
    {
        $credit = app(CreditCalculator::class)->calculate(300_000_000, 60_000_000, 36);
        $purchase = $this->purchase([
            'payment_method' => 'credit',
            'down_payment' => 60_000_000,
            'tenor_months' => 36,
            'interest_rate' => $credit['interest_rate'],
            'monthly_installment' => $credit['monthly_installment'],
            'address' => 'Jl. Merdeka No. 10, Bandung',
        ]);

        $this->assertSame(7_867_000, $purchase->monthly_installment);

        $this->actingAs($this->admin)->get(route('admin.purchase-requests.show', $purchase))
            ->assertOk()
            ->assertSeeInOrder([
                'Rincian Harga · Kredit',
                'Rp 300.000.000',
                'DP 20,0%', 'Rp 60.000.000',
                'Pokok pinjaman', 'Rp 240.000.000',
                '36 bulan',
                '6% / tahun',
                'Total bunga', 'Rp 43.212.000',
                'Cicilan per bulan', 'Rp 7.867.000',
                'Total pembayaran', 'Rp 343.212.000',
            ])
            ->assertSee('Jl. Merdeka No. 10, Bandung')
            ->assertSee('Tetap: Menunggu')
            ->assertSee('<option value="processing"', false)
            ->assertDontSee('<option value="approved"', false);
    }

    public function test_detail_cash_dan_peringatan_stok_habis(): void
    {
        $soldOut = Car::factory()->outOfStock()->create(['price' => 200_000_000]);
        $purchase = $this->purchase(['payment_method' => 'cash', 'car_price' => 200_000_000], $soldOut);

        $this->actingAs($this->admin)->get(route('admin.purchase-requests.show', $purchase))
            ->assertOk()
            ->assertSee('Total pembayaran (cash)')
            ->assertDontSee('Cicilan per bulan')
            ->assertSee('Stok mobil ini 0');
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function validTransitionProvider(): array
    {
        // [dari, ke, perubahan stok]
        return [
            'pending → processing' => ['pending', 'processing', 0],
            'pending → rejected' => ['pending', 'rejected', 0],
            'pending → cancelled' => ['pending', 'cancelled', 0],
            'processing → approved (stok −1)' => ['processing', 'approved', -1],
            'processing → rejected' => ['processing', 'rejected', 0],
            'processing → cancelled' => ['processing', 'cancelled', 0],
            'approved → completed (stok tetap)' => ['approved', 'completed', 0],
            'approved → rejected (stok +1)' => ['approved', 'rejected', 1],
            'approved → cancelled (stok +1)' => ['approved', 'cancelled', 1],
        ];
    }

    #[DataProvider('validTransitionProvider')]
    public function test_transisi_valid_dan_efek_stok(string $from, string $to, int $stockChange): void
    {
        $purchase = $this->purchase(['status' => $from]);

        $this->changeStatus($purchase, ['status' => $to, 'admin_note' => 'Catatan admin untuk customer.'])
            ->assertRedirect(route('admin.purchase-requests.show', $purchase))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Status pengajuan diubah'));

        $this->assertSame($to, $purchase->fresh()->status);
        $this->assertSame('Catatan admin untuk customer.', $purchase->fresh()->admin_note);
        $this->assertSame(3 + $stockChange, $this->stock());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidTransitionProvider(): array
    {
        return [
            'pending → approved (harus diproses dulu)' => ['pending', 'approved'],
            'pending → completed' => ['pending', 'completed'],
            'processing → pending' => ['processing', 'pending'],
            'processing → completed' => ['processing', 'completed'],
            'approved → processing' => ['approved', 'processing'],
            'completed → cancelled' => ['completed', 'cancelled'],
            'rejected → approved' => ['rejected', 'approved'],
            'cancelled → processing' => ['cancelled', 'processing'],
            'status tidak dikenal' => ['pending', 'confirmed'],
        ];
    }

    #[DataProvider('invalidTransitionProvider')]
    public function test_transisi_tidak_valid_ditolak(string $from, string $to): void
    {
        $purchase = $this->purchase(['status' => $from]);

        $this->changeStatus($purchase, ['status' => $to, 'admin_note' => 'Catatan percobaan.'])
            ->assertRedirect(route('admin.purchase-requests.show', $purchase))
            ->assertSessionHasErrors('status');

        $this->assertSame($from, $purchase->fresh()->status);
        $this->assertNull($purchase->fresh()->admin_note);
        $this->assertSame(3, $this->stock());
    }

    public function test_menyetujui_saat_stok_0_ditolak_dengan_pesan_jelas(): void
    {
        $soldOut = Car::factory()->outOfStock()->create(['name' => 'Fortuner', 'year' => 2025]);
        $purchase = $this->purchase(['status' => 'processing'], $soldOut);

        $this->changeStatus($purchase, ['status' => 'approved', 'admin_note' => 'Siap diproses.'])
            ->assertRedirect(route('admin.purchase-requests.show', $purchase))
            ->assertSessionHas('error', 'Stok mobil "Fortuner 2025" habis, pengajuan tidak bisa disetujui.');

        $this->assertSame('processing', $purchase->fresh()->status);
        $this->assertNull($purchase->fresh()->admin_note);
        $this->assertSame(0, $this->stock($soldOut));
    }

    public function test_stok_terakhir_hanya_bisa_disetujui_sekali(): void
    {
        $lastUnit = Car::factory()->create(['stock' => 1]);
        $first = $this->purchase(['status' => 'processing'], $lastUnit);
        $second = $this->purchase(['status' => 'processing'], $lastUnit);

        $this->changeStatus($first, ['status' => 'approved'])->assertSessionHas('success');
        $this->changeStatus($second, ['status' => 'approved'])->assertSessionHas('error');

        $this->assertSame(0, $this->stock($lastUnit));
        $this->assertSame('approved', $first->fresh()->status);
        $this->assertSame('processing', $second->fresh()->status);

        // Pembatalan yang sudah disetujui mengembalikan unit, lalu pengajuan kedua bisa disetujui.
        $this->changeStatus($first, ['status' => 'cancelled', 'admin_note' => 'Customer membatalkan.']);
        $this->assertSame(1, $this->stock($lastUnit));
        $this->changeStatus($second, ['status' => 'approved'])->assertSessionHas('success');
        $this->assertSame(0, $this->stock($lastUnit));
    }

    public function test_action_memeriksa_ulang_status_di_dalam_transaksi(): void
    {
        $purchase = $this->purchase(['status' => 'processing']);
        $stale = PurchaseRequest::find($purchase->id);

        app(ChangePurchaseRequestStatus::class)->handle($purchase, 'approved', null);
        $this->assertSame(2, $this->stock());

        // Salinan lama masih "processing", tetapi action membaca ulang baris yang dikunci:
        // persetujuan kedua menjadi "status tetap" sehingga stok tidak berkurang dua kali.
        app(ChangePurchaseRequestStatus::class)->handle($stale, 'approved', null);
        $this->assertSame(2, $this->stock());
        $this->assertSame('approved', $purchase->fresh()->status);

        // Transisi yang tidak valid dari status terbaru tetap ditolak, walau salinannya lama.
        $purchase->update(['status' => 'completed']);
        try {
            app(ChangePurchaseRequestStatus::class)->handle($stale, 'rejected', 'Salinan lama.');
            $this->fail('Transisi dari Selesai seharusnya ditolak.');
        } catch (DomainException $e) {
            $this->assertSame('Status pengajuan tidak bisa diubah dari Selesai ke Ditolak.', $e->getMessage());
        }

        $this->assertSame('completed', $purchase->fresh()->status);
        $this->assertSame(2, $this->stock());
    }

    /**
     * Simulasikan admin lain yang mengubah status (lewat action) tepat setelah request ini memuat pengajuan,
     * sehingga validasi memakai salinan lama.
     */
    private function otherAdminChangesStatusFirst(PurchaseRequest $purchaseRequest, string $status): void
    {
        $done = false;

        PurchaseRequest::retrieved(function (PurchaseRequest $retrieved) use ($purchaseRequest, $status, &$done) {
            if (! $done && $retrieved->is($purchaseRequest)) {
                $done = true;
                app(ChangePurchaseRequestStatus::class)->handle(PurchaseRequest::find($retrieved->id), $status, 'Diputuskan admin lain.');
            }
        });
    }

    public function test_persetujuan_ganda_menampilkan_info_tanpa_mengubah_stok_lagi(): void
    {
        $purchase = $this->purchase(['status' => 'processing']);
        $this->otherAdminChangesStatusFirst($purchase, 'approved');

        $this->changeStatus($purchase, ['status' => 'approved', 'admin_note' => ''])
            ->assertRedirect(route('admin.purchase-requests.show', $purchase))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Pengajuan ini sudah berstatus Disetujui. Tidak ada perubahan.')
            ->assertSessionMissing('success');

        $this->assertSame('approved', $purchase->fresh()->status);
        $this->assertSame(2, $this->stock(), 'Stok hanya berkurang sekali.');

        $this->actingAs($this->admin)->get(route('admin.purchase-requests.show', $purchase))
            ->assertSee('alert-info', false)
            ->assertSee('Pengajuan ini sudah berstatus Disetujui. Tidak ada perubahan.')
            ->assertDontSee('diubah dari');
    }

    public function test_perubahan_status_nyata_tetap_memakai_pesan_diubah(): void
    {
        $purchase = $this->purchase(['status' => 'processing']);

        $this->changeStatus($purchase, ['status' => 'approved'])
            ->assertSessionHas('success', 'Status pengajuan diubah dari Diproses menjadi Disetujui.')
            ->assertSessionMissing('status');
    }

    public function test_catatan_wajib_saat_menolak_atau_membatalkan(): void
    {
        $purchase = $this->purchase(['status' => 'processing']);

        foreach (['rejected', 'cancelled'] as $status) {
            $this->changeStatus($purchase, ['status' => $status, 'admin_note' => ''])
                ->assertSessionHasErrors(['admin_note' => 'Catatan admin wajib diisi saat menolak atau membatalkan pengajuan (alasan untuk customer).']);
        }

        $this->changeStatus($purchase, ['status' => 'rejected', 'admin_note' => 'abcd'])->assertSessionHasErrors('admin_note');
        $this->changeStatus($purchase, ['status' => 'approved', 'admin_note' => str_repeat('a', 1001)])->assertSessionHasErrors('admin_note');

        $this->assertSame('processing', $purchase->fresh()->status);
        $this->assertSame(3, $this->stock());

        $this->changeStatus($purchase, ['status' => 'approved', 'admin_note' => ''])->assertSessionHasNoErrors();
        $this->assertSame('approved', $purchase->fresh()->status);
        $this->assertNull($purchase->fresh()->admin_note);
    }

    public function test_hanya_mengubah_catatan_tidak_mengubah_stok(): void
    {
        $purchase = $this->purchase(['status' => 'approved']);

        $this->changeStatus($purchase, ['status' => '', 'admin_note' => 'Menunggu pelunasan.'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Catatan admin pengajuan berhasil disimpan.');
        $this->changeStatus($purchase, ['status' => 'approved', 'admin_note' => 'Jadwal serah terima Senin.'])
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $purchase->fresh()->status);
        $this->assertSame('Jadwal serah terima Senin.', $purchase->fresh()->admin_note);
        $this->assertSame(3, $this->stock());
    }

    public function test_riwayat_di_detail_pengguna_kini_tertaut(): void
    {
        $purchase = $this->purchase();

        $this->actingAs($this->admin)->get(route('admin.users.show', $this->customer))
            ->assertOk()
            ->assertSee('href="'.route('admin.purchase-requests.show', $purchase).'"', false);
    }

    public function test_seeder_pengajuan_konsisten_dengan_stok_dan_kredit_serta_aman_dijalankan_ulang(): void
    {
        config(['dealer.seed.customer_password' => 'rahasia-lokal']);
        $this->seed([CarSeeder::class, PromoSeeder::class, CustomerSeeder::class]);
        $initialStock = Car::pluck('stock', 'slug');

        $this->seed(PurchaseRequestSeeder::class);
        $afterFirstRun = Car::pluck('stock', 'slug');
        $this->seed(PurchaseRequestSeeder::class);

        $seeded = PurchaseRequest::with('car:id,slug')->get();
        $this->assertCount(9, $seeded);
        $this->assertEqualsCanonicalizing(PurchaseRequest::STATUSES, $seeded->pluck('status')->unique()->values()->all());
        $this->assertEquals($afterFirstRun, Car::pluck('stock', 'slug'), 'Menjalankan ulang tidak boleh mengubah stok.');

        // Stok akhir = stok awal − pengajuan approved/completed per mobil.
        $sold = $seeded->whereIn('status', ['approved', 'completed'])->countBy(fn ($purchase) => $purchase->car->slug);
        foreach ($initialStock as $slug => $stock) {
            $this->assertSame($stock - ($sold[$slug] ?? 0), $afterFirstRun[$slug], "Stok {$slug} tidak konsisten.");
        }
        $this->assertSame(2, $sold->sum());

        // Nilai kredit sama dengan CreditCalculator; harga memakai promo aktif (Avanza −Rp 15 jt).
        $calculator = app(CreditCalculator::class);
        foreach ($seeded->where('payment_method', 'credit') as $purchase) {
            $expected = $calculator->calculate($purchase->car_price, $purchase->down_payment, $purchase->tenor_months);
            $this->assertSame($expected['monthly_installment'], $purchase->monthly_installment);
            $this->assertEquals($expected['interest_rate'], (float) $purchase->interest_rate);
        }
        $this->assertSame(270_000_000, $seeded->first(fn ($purchase) => $purchase->car->slug === 'toyota-avanza-1-5-g-cvt-2025')->car_price);

        // Status tolak/batal punya catatan admin.
        $this->assertTrue($seeded->whereIn('status', ['rejected', 'cancelled'])->every(fn ($purchase) => filled($purchase->admin_note)));
    }

    public function test_seeder_pengajuan_dilewati_tanpa_customer_dummy(): void
    {
        $this->seed(CarSeeder::class);
        $this->seed(PurchaseRequestSeeder::class);

        $this->assertSame(0, PurchaseRequest::count());
    }

    public function test_seeder_pengajuan_tidak_berjalan_di_production(): void
    {
        config(['dealer.seed.customer_password' => 'rahasia-lokal']);
        $this->seed([CarSeeder::class, CustomerSeeder::class]);
        $stock = Car::pluck('stock', 'slug');
        $this->app['env'] = 'production';

        // Dipanggil langsung: perintah db:seed di production meminta konfirmasi interaktif.
        $this->app->make(PurchaseRequestSeeder::class)->run();

        $this->assertSame(0, PurchaseRequest::count());
        $this->assertEquals($stock, Car::pluck('stock', 'slug'));
    }
}
