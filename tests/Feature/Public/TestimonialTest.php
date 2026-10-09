<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\Testimonial;
use App\Models\User;
use App\Notifications\Admin\TestimonialActivity;
use Database\Seeders\CarSeeder;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\PromoSeeder;
use Database\Seeders\PurchaseRequestSeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Ulasan customer: form di Pengajuan Saya, aturan kepemilikan & status, tampil di beranda.
 */
class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $admin;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create(['name' => 'Budi Santoso']);
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Navara VL 4x4 AT', 'year' => 2024,
        ]);
    }

    private function purchase(string $status = PurchaseRequest::STATUS_COMPLETED, ?User $owner = null): PurchaseRequest
    {
        return PurchaseRequest::factory()->recycle($owner ?? $this->customer)->recycle($this->car)->status($status)->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return ['rating' => 5, 'comment' => 'Pelayanan ramah, proses cepat, dan unit diantar tepat waktu.', ...$overrides];
    }

    // ---------- Pengajuan Saya ----------

    public function test_tombol_beri_ulasan_hanya_untuk_pembelian_selesai(): void
    {
        $completed = $this->purchase();
        $pending = $this->purchase(PurchaseRequest::STATUS_PENDING);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertOk()
            ->assertSee('href="'.route('account.testimonials.edit', $completed).'"', false)
            ->assertDontSee('href="'.route('account.testimonials.edit', $pending).'"', false);
    }

    // ---------- Kirim & ubah ----------

    public function test_customer_mengirim_ulasan_dan_admin_diberi_tahu(): void
    {
        $purchase = $this->purchase();

        $this->actingAs($this->customer)->get(route('account.testimonials.edit', $purchase))
            ->assertOk()
            ->assertSee('Beri Ulasan')
            ->assertSee('Nissan Navara VL 4x4 AT 2024')
            ->assertSee('Budi S.');

        $this->actingAs($this->customer)->put(route('account.testimonials.update', $purchase), $this->payload())
            ->assertRedirect(route('account.purchase-requests.index')."#pengajuan-{$purchase->id}")
            ->assertSessionHas('success');

        $testimonial = Testimonial::sole();
        $this->assertSame($this->customer->id, $testimonial->user_id);
        $this->assertSame(5, $testimonial->rating);
        $this->assertSame(Testimonial::STATUS_PENDING, $testimonial->status);

        $notification = $this->admin->notifications()->sole();
        $this->assertSame(TestimonialActivity::class, $notification->type);
        $this->assertSame('Ulasan Baru · Budi Santoso', $notification->data['title']);
        $this->assertStringStartsWith('★★★★★ · Nissan Navara VL 4x4 AT 2024', $notification->data['message']);
        $this->assertSame(['status' => 'pending'], $notification->data['params']);

        $this->actingAs($this->customer)->get(route('account.purchase-requests.index'))
            ->assertSee('Menunggu persetujuan admin')
            ->assertSee('Ubah Ulasan');
    }

    public function test_satu_ulasan_per_pengajuan_dan_perbaikan_kembali_menunggu(): void
    {
        $purchase = $this->purchase();
        $testimonial = Testimonial::factory()->recycle($this->customer)->rejected('Mohon hapus nomor HP.')->create(['purchase_request_id' => $purchase->id]);

        $this->actingAs($this->customer)->get(route('account.testimonials.edit', $purchase))
            ->assertOk()
            ->assertSee('Ulasan sebelumnya ditolak')
            ->assertSee('Mohon hapus nomor HP.');

        $this->actingAs($this->customer)->put(route('account.testimonials.update', $purchase), $this->payload(['rating' => 4]));

        $this->assertSame(1, Testimonial::count());
        $testimonial->refresh();
        $this->assertSame(4, $testimonial->rating);
        $this->assertSame(Testimonial::STATUS_PENDING, $testimonial->status);
        $this->assertNull($testimonial->admin_note);
        $this->assertSame('Ulasan Diperbarui · Budi Santoso', $this->admin->notifications()->sole()->data['title']);
    }

    public function test_ulasan_disetujui_terkunci(): void
    {
        $purchase = $this->purchase();
        Testimonial::factory()->recycle($this->customer)->approved()->create(['purchase_request_id' => $purchase->id, 'rating' => 5]);

        $this->actingAs($this->customer)->get(route('account.testimonials.edit', $purchase))
            ->assertRedirect(route('account.purchase-requests.index'))
            ->assertSessionHas('status', 'Ulasan Anda untuk pembelian ini sudah disetujui dan tidak bisa diubah lagi.');

        $this->actingAs($this->customer)->put(route('account.testimonials.update', $purchase), $this->payload(['rating' => 1]));

        $this->assertSame(5, Testimonial::sole()->rating);
        $this->assertSame(0, $this->admin->notifications()->count());
    }

    public function test_pembelian_belum_selesai_ditolak(): void
    {
        $purchase = $this->purchase(PurchaseRequest::STATUS_APPROVED);

        $this->actingAs($this->customer)->get(route('account.testimonials.edit', $purchase))
            ->assertRedirect(route('account.purchase-requests.index'))
            ->assertSessionHas('error', 'Ulasan hanya bisa diberikan untuk pembelian yang sudah selesai.');

        $this->actingAs($this->customer)->put(route('account.testimonials.update', $purchase), $this->payload());
        $this->assertSame(0, Testimonial::count());
    }

    public function test_pengajuan_milik_orang_lain_404_dan_tamu_ke_login(): void
    {
        $others = $this->purchase(owner: User::factory()->create());

        // Tamu dulu (setelah actingAs, request berikutnya tetap login).
        $this->put(route('account.testimonials.update', $this->purchase()), $this->payload())->assertRedirect(route('login'));

        $this->actingAs($this->customer)->get(route('account.testimonials.edit', $others))->assertNotFound();
        $this->actingAs($this->customer)->put(route('account.testimonials.update', $others), $this->payload())->assertNotFound();

        $this->assertSame(0, Testimonial::count());
    }

    public function test_validasi_rating_dan_ulasan(): void
    {
        $purchase = $this->purchase();

        foreach ([
            ['rating' => null, 'comment' => str_repeat('a', 30)],
            ['rating' => 6, 'comment' => str_repeat('a', 30)],
            ['rating' => 0, 'comment' => str_repeat('a', 30)],
            ['rating' => 5, 'comment' => '   terlalu pendek   '],
            ['rating' => 5, 'comment' => str_repeat('a', 1001)],
        ] as $payload) {
            $this->actingAs($this->customer)->put(route('account.testimonials.update', $purchase), $payload)
                ->assertSessionHasErrors();
        }

        $this->actingAs($this->customer)->put(route('account.testimonials.update', $purchase), ['rating' => 'x', 'comment' => ''])
            ->assertSessionHasErrors(['rating' => 'Pilih rating 1 sampai 5 bintang.', 'comment']);

        $this->assertSame(0, Testimonial::count());
    }

    // ---------- Beranda ----------

    public function test_beranda_hanya_menampilkan_ulasan_disetujui_dengan_nama_disingkat(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('Kata Pelanggan');

        Testimonial::factory()->recycle($this->customer)->approved()->create([
            'purchase_request_id' => $this->purchase()->id, 'rating' => 5, 'comment' => 'Ulasan yang disetujui admin JAF.',
        ]);
        Testimonial::factory()->recycle($this->customer)->approved()->create([
            'purchase_request_id' => $this->purchase()->id, 'rating' => 4, 'comment' => 'Ulasan kedua yang disetujui.',
        ]);
        Testimonial::factory()->create(['comment' => 'Ulasan yang masih menunggu moderasi.']);
        Testimonial::factory()->rejected()->create(['comment' => 'Ulasan yang ditolak admin.']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Kata Pelanggan')
            ->assertSee('Ulasan yang disetujui admin JAF.')
            ->assertSee('Ulasan kedua yang disetujui.')
            ->assertDontSee('Ulasan yang masih menunggu moderasi.')
            ->assertDontSee('Ulasan yang ditolak admin.')
            ->assertSee('Budi S.')
            ->assertDontSee('Budi Santoso')
            ->assertDontSee($this->customer->email)
            ->assertSee('Membeli Nissan Navara VL 4x4 AT 2024')
            ->assertSeeInOrder(['4,5', 'dari 5', '2 ulasan'])
            ->assertSee('aria-label="Rating 5 dari 5"', false);
    }

    public function test_ulasan_di_escape(): void
    {
        Testimonial::factory()->approved()->create(['comment' => '<script>alert(1)</script> ulasan berbahaya']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_beranda_tanpa_n_plus_1(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->get(route('home'))->assertOk();
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        Testimonial::factory()->approved()->create();
        $withOne = $count();
        Testimonial::factory()->count(4)->approved()->create();

        $this->assertSame($withOne, $count());
    }

    public function test_nama_publik(): void
    {
        $this->assertSame('Budi S.', Testimonial::publicName('Budi Santoso'));
        $this->assertSame('Siti R.', Testimonial::publicName('  Siti  Nur  Rahmawati '));
        $this->assertSame('Andi', Testimonial::publicName('Andi'));
        $this->assertSame('Ayu Ç.', Testimonial::publicName('Ayu çahaya'));
    }

    // ---------- Seeder ----------

    public function test_seeder_ulasan_aman_dijalankan_ulang(): void
    {
        config(['dealer.seed.customer_password' => 'rahasia-lokal']);
        $this->seed([CarSeeder::class, PromoSeeder::class, CustomerSeeder::class, PurchaseRequestSeeder::class]);

        $this->seed(TestimonialSeeder::class);
        $this->seed(TestimonialSeeder::class);

        $seeded = Testimonial::with('purchaseRequest')->get();
        $this->assertCount(3, $seeded);
        $this->assertTrue($seeded->every(fn (Testimonial $testimonial) => $testimonial->isApproved()
            && $testimonial->purchaseRequest->status === PurchaseRequest::STATUS_COMPLETED
            && $testimonial->purchaseRequest->user_id === $testimonial->user_id));

        $this->get(route('home'))->assertSee('Kata Pelanggan')->assertSee('Nur A.');
    }
}
