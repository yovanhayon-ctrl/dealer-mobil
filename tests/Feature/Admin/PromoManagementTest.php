<?php

namespace Tests\Feature\Admin;

use App\Models\Car;
use App\Models\Promo;
use App\Models\User;
use Closure;
use Database\Seeders\CarSeeder;
use Database\Seeders\PromoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PromoManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        // Tanggal tetap (WIB) agar status promo bisa diuji pasti.
        $this->travelTo(Carbon::parse('2026-09-26 10:00:00', 'Asia/Jakarta'));
        Storage::fake('public');

        $this->admin = User::factory()->admin()->create();
        $this->car = Car::factory()->create(['name' => 'Avanza', 'year' => 2025, 'price' => 285_000_000]);
    }

    /**
     * Data form promo khusus mobil yang valid; timpa sebagian lewat $overrides.
     *
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return [
            'car_id' => $this->car->id,
            'title' => 'Diskon Spesial Avanza',
            'description' => 'Potongan harga selama September.',
            'discount_amount' => '15.000.000',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'is_active' => '1',
            ...$overrides,
        ];
    }

    private function banner(string $name = 'banner.jpg', int $width = 1200, int $height = 400): UploadedFile
    {
        return UploadedFile::fake()->image($name, $width, $height);
    }

    private function store(array $data)
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.promos.create'))
            ->post(route('admin.promos.store'), $data);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $promo = Promo::factory()->create();

        $this->get('/admin/promo')->assertRedirect(route('login'));
        $this->get('/admin/promo/tambah')->assertRedirect(route('login'));
        $this->post('/admin/promo', $this->validData())->assertRedirect(route('login'));
        $this->get("/admin/promo/{$promo->id}/ubah")->assertRedirect(route('login'));
        $this->put("/admin/promo/{$promo->id}", $this->validData())->assertRedirect(route('login'));
        $this->delete("/admin/promo/{$promo->id}")->assertRedirect(route('login'));

        $this->assertSame(1, Promo::count());
        $this->assertSame($promo->title, $promo->fresh()->title);
    }

    public function test_customer_mendapat_403(): void
    {
        $promo = Promo::factory()->create();

        $this->actingAs(User::factory()->create());
        $this->get('/admin/promo')->assertForbidden();
        $this->get('/admin/promo/tambah')->assertForbidden();
        $this->post('/admin/promo', $this->validData())->assertForbidden();
        $this->get("/admin/promo/{$promo->id}/ubah")->assertForbidden();
        $this->put("/admin/promo/{$promo->id}", $this->validData())->assertForbidden();
        $this->delete("/admin/promo/{$promo->id}")->assertForbidden();

        $this->assertSame(1, Promo::count());
        $this->assertSame($promo->title, $promo->fresh()->title);
    }

    public function test_halaman_tambah_hanya_menawarkan_mobil_aktif(): void
    {
        Car::factory()->create(['name' => 'Sigra', 'year' => 2021, 'is_active' => false]);

        $this->actingAs($this->admin)->get(route('admin.promos.create'))
            ->assertOk()
            ->assertSee('Promo Umum (semua mobil)')
            ->assertSee("{$this->car->brand->name} Avanza 2025")
            ->assertDontSee('Sigra');
    }

    public function test_admin_bisa_menambah_promo_khusus_mobil(): void
    {
        $this->store($this->validData())
            ->assertRedirect(route('admin.promos.index'))
            ->assertSessionHas('success', 'Promo "Diskon Spesial Avanza" berhasil ditambahkan.');

        $promo = Promo::sole();

        $this->assertSame($this->car->id, $promo->car_id);
        $this->assertSame('diskon-spesial-avanza', $promo->slug);
        $this->assertSame(15_000_000, $promo->discount_amount);
        $this->assertSame('2026-09-01', $promo->start_date->toDateString());
        $this->assertSame('2026-09-30', $promo->end_date->toDateString());
        $this->assertTrue($promo->is_active);
        $this->assertNull($promo->image);
    }

    public function test_admin_bisa_menambah_promo_umum_tanpa_diskon(): void
    {
        $this->store($this->validData([
            'car_id' => '',
            'title' => 'Gratis Servis 3 Tahun',
            'discount_amount' => '',
            'is_active' => null,
        ]))->assertSessionHasNoErrors();

        $promo = Promo::sole();

        $this->assertNull($promo->car_id);
        $this->assertNull($promo->discount_amount);
        $this->assertFalse($promo->is_active);
        $this->assertTrue($promo->isGeneral());
    }

    public function test_slug_unik_untuk_judul_yang_sama(): void
    {
        $this->store($this->validData());
        $this->store($this->validData());

        $this->assertSame(['diskon-spesial-avanza', 'diskon-spesial-avanza-2'], Promo::orderBy('id')->pluck('slug')->all());
    }

    public function test_edit_mengubah_data_tetapi_slug_tidak_berubah(): void
    {
        $promo = Promo::factory()->forCar($this->car, 5_000_000)->create([
            'title' => 'Diskon Lama',
            'slug' => 'diskon-lama',
        ]);

        $this->actingAs($this->admin)->get(route('admin.promos.edit', $promo))
            ->assertOk()
            ->assertSee('Slug URL: diskon-lama');

        $this->actingAs($this->admin)->put(route('admin.promos.update', $promo), $this->validData(['title' => 'Diskon Baru']))
            ->assertRedirect(route('admin.promos.index'))
            ->assertSessionHas('success', 'Promo "Diskon Baru" berhasil diperbarui.');

        $promo->refresh();
        $this->assertSame('Diskon Baru', $promo->title);
        $this->assertSame('diskon-lama', $promo->slug);
        $this->assertSame(15_000_000, $promo->discount_amount);
    }

    public function test_hapus_promo_menghapus_baris_dan_banner(): void
    {
        $image = $this->banner()->store('promos', 'public');
        $promo = Promo::factory()->create(['title' => 'Promo Lama', 'image' => $image]);

        $this->actingAs($this->admin)->delete(route('admin.promos.destroy', $promo))
            ->assertRedirect(route('admin.promos.index'))
            ->assertSessionHas('success', 'Promo "Promo Lama" berhasil dihapus.');

        $this->assertModelMissing($promo);
        Storage::disk('public')->assertMissing($image);
    }

    public function test_validasi_judul_dan_tanggal(): void
    {
        $this->store($this->validData(['title' => '   ', 'start_date' => '', 'end_date' => '']))
            ->assertSessionHasErrors(['title', 'start_date', 'end_date']);

        $this->store($this->validData(['start_date' => '2026-09-30', 'end_date' => '2026-09-29']))
            ->assertSessionHasErrors(['end_date' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);

        $this->store($this->validData(['start_date' => '30/09/2026']))
            ->assertSessionHasErrors(['start_date' => 'Tanggal mulai tidak valid.']);

        $this->assertSame(0, Promo::count());

        $this->store($this->validData(['start_date' => '2026-09-30', 'end_date' => '2026-09-30']))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Promo::count());
    }

    public function test_diskon_harus_lebih_kecil_dari_harga_mobil(): void
    {
        $this->store($this->validData(['discount_amount' => '285.000.000']))
            ->assertSessionHasErrors(['discount_amount' => 'Diskon harus lebih kecil dari harga mobil (Rp 285.000.000).']);

        $this->store($this->validData(['discount_amount' => '300.000.000']))
            ->assertSessionHasErrors('discount_amount');

        $this->store($this->validData(['discount_amount' => '0']))
            ->assertSessionHasErrors(['discount_amount' => 'Diskon minimal Rp 1.']);

        $this->assertSame(0, Promo::count());

        $this->store($this->validData(['discount_amount' => '284.999.999']))->assertSessionHasNoErrors();
        $this->assertSame(284_999_999, Promo::sole()->discount_amount);
    }

    public function test_diskon_wajib_untuk_promo_khusus_mobil(): void
    {
        $this->store($this->validData(['discount_amount' => '']))
            ->assertSessionHasErrors(['discount_amount' => 'Diskon wajib diisi untuk promo khusus mobil.']);

        $this->assertSame(0, Promo::count());
    }

    public function test_diskon_ditolak_untuk_promo_umum(): void
    {
        $this->store($this->validData(['car_id' => '', 'discount_amount' => '5.000.000']))
            ->assertSessionHasErrors(['discount_amount' => 'Diskon hanya untuk promo khusus mobil. Kosongkan untuk promo umum.']);

        $this->assertSame(0, Promo::count());
    }

    public function test_mobil_tidak_dikenal_atau_nonaktif_ditolak_saat_tambah(): void
    {
        $inactive = Car::factory()->create(['is_active' => false, 'price' => 100_000_000]);

        $this->store($this->validData(['car_id' => 999999]))->assertSessionHasErrors('car_id');
        $this->store($this->validData(['car_id' => $inactive->id, 'discount_amount' => '1.000.000']))
            ->assertSessionHasErrors(['car_id' => 'Mobil yang dipilih sedang nonaktif.']);

        $this->assertSame(0, Promo::count());
    }

    public function test_mobil_yang_kini_nonaktif_tetap_diterima_saat_edit_promonya(): void
    {
        $promo = Promo::factory()->forCar($this->car, 5_000_000)->create();
        $this->car->update(['is_active' => false]);

        $this->actingAs($this->admin)->get(route('admin.promos.edit', $promo))
            ->assertOk()
            ->assertSee('Avanza 2025 (nonaktif)');

        $this->actingAs($this->admin)->put(route('admin.promos.update', $promo), $this->validData(['title' => 'Tetap']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Tetap', $promo->fresh()->title);
        $this->assertSame($this->car->id, $promo->fresh()->car_id);
    }

    public function test_upload_banner_valid_tersimpan(): void
    {
        $this->store($this->validData(['image' => $this->banner('banner-asli.webp', 1600, 500)]))
            ->assertSessionHasNoErrors();

        $promo = Promo::sole();

        $this->assertStringStartsWith('promos/', $promo->image);
        $this->assertStringNotContainsString('banner-asli', $promo->image);
        Storage::disk('public')->assertExists($promo->image);
        $this->assertSame(Storage::disk('public')->url($promo->image), $promo->image_url);
    }

    /**
     * File dibuat di dalam test (lewat closure) karena data provider berjalan sebelum aplikasi disiapkan.
     *
     * @return array<string, array{Closure(self): UploadedFile, string}>
     */
    public static function invalidBannerProvider(): array
    {
        $wrongType = 'Banner harus berupa gambar JPG, JPEG, PNG, atau WEBP.';
        $tooSmall = 'Banner minimal berukuran 1200×400 piksel.';

        return [
            'svg' => [fn () => UploadedFile::fake()->createWithContent(
                'banner.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="400"></svg>',
            ), $wrongType],
            'pdf' => [fn () => UploadedFile::fake()->create('banner.pdf', 100, 'application/pdf'), $wrongType],
            'lebih dari 2 MB' => [fn (self $test) => $test->banner('besar.png')->size(2049), 'Ukuran banner maksimal 2 MB.'],
            'lebar 1199 px' => [fn (self $test) => $test->banner('sempit.jpg', 1199, 400), $tooSmall],
            'tinggi 399 px' => [fn (self $test) => $test->banner('pendek.jpg', 1200, 399), $tooSmall],
        ];
    }

    #[DataProvider('invalidBannerProvider')]
    public function test_banner_tidak_valid_ditolak(Closure $makeFile, string $message): void
    {
        $this->store($this->validData(['image' => $makeFile($this)]))
            ->assertRedirect(route('admin.promos.create'))
            ->assertSessionHasErrors(['image' => $message]);

        $this->assertSame(0, Promo::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_ganti_banner_menghapus_file_lama(): void
    {
        $old = $this->banner('lama.jpg')->store('promos', 'public');
        $promo = Promo::factory()->forCar($this->car)->create(['image' => $old]);

        $this->actingAs($this->admin)->put(route('admin.promos.update', $promo), $this->validData([
            'image' => $this->banner('baru.png'),
        ]))->assertSessionHasNoErrors();

        $promo->refresh();
        $this->assertNotSame($old, $promo->image);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($promo->image);
    }

    public function test_hapus_banner_tanpa_upload_baru(): void
    {
        $old = $this->banner()->store('promos', 'public');
        $promo = Promo::factory()->forCar($this->car)->create(['image' => $old]);

        $this->actingAs($this->admin)->put(route('admin.promos.update', $promo), $this->validData(['remove_image' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertNull($promo->fresh()->image);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_edit_tanpa_banner_baru_mempertahankan_banner_lama(): void
    {
        $old = $this->banner()->store('promos', 'public');
        $promo = Promo::factory()->forCar($this->car)->create(['image' => $old]);

        $this->actingAs($this->admin)->put(route('admin.promos.update', $promo), $this->validData())
            ->assertSessionHasNoErrors();

        $this->assertSame($old, $promo->fresh()->image);
        Storage::disk('public')->assertExists($old);
    }

    public function test_status_dihitung_dari_tanggal_dan_is_active(): void
    {
        $status = fn (array $attributes) => Promo::factory()->make($attributes)->status;

        $this->assertSame(Promo::STATUS_RUNNING, $status(['start_date' => '2026-09-01', 'end_date' => '2026-09-30']));
        $this->assertSame(Promo::STATUS_RUNNING, $status(['start_date' => '2026-09-26', 'end_date' => '2026-09-26']));
        $this->assertSame(Promo::STATUS_SCHEDULED, $status(['start_date' => '2026-09-27', 'end_date' => '2026-10-30']));
        $this->assertSame(Promo::STATUS_ENDED, $status(['start_date' => '2026-09-01', 'end_date' => '2026-09-25']));
        $this->assertSame(Promo::STATUS_INACTIVE, $status(['is_active' => false, 'start_date' => '2026-09-01', 'end_date' => '2026-09-30']));
        $this->assertSame(Promo::STATUS_INACTIVE, $status(['is_active' => false, 'start_date' => '2026-01-01', 'end_date' => '2026-01-31']));
    }

    public function test_daftar_menampilkan_kolom_status_dan_format(): void
    {
        Promo::factory()->forCar($this->car, 15_000_000)->create([
            'title' => 'Diskon Avanza', 'start_date' => '2026-09-01', 'end_date' => '2026-09-30',
        ]);
        Promo::factory()->create(['title' => 'Promo Oktober', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31']);
        Promo::factory()->create(['title' => 'Promo Agustus', 'start_date' => '2026-08-01', 'end_date' => '2026-08-31']);
        Promo::factory()->inactive()->create(['title' => 'Promo Mati']);

        $this->actingAs($this->admin)->get(route('admin.promos.index'))
            ->assertOk()
            ->assertSeeInOrder(['Diskon Avanza', "{$this->car->brand->name} Avanza 2025", 'Rp 15.000.000', '01 Sep 2026 – 30 Sep 2026', 'Berjalan'])
            ->assertSee('Promo Umum')
            ->assertSee('Terjadwal')
            ->assertSee('Berakhir')
            ->assertSee('Nonaktif');
    }

    public function test_filter_kata_kunci_status_dan_jenis(): void
    {
        Promo::factory()->forCar($this->car)->create(['title' => 'Diskon Avanza']);
        Promo::factory()->create(['title' => 'Gratis Servis']);
        Promo::factory()->scheduled()->create(['title' => 'Promo Natal']);
        Promo::factory()->ended()->create(['title' => 'Promo Kemerdekaan']);
        Promo::factory()->inactive()->create(['title' => 'Promo Dimatikan']);

        $titles = fn (array $query) => $this->actingAs($this->admin)
            ->get(route('admin.promos.index', $query))
            ->assertOk()
            ->viewData('promos')
            ->pluck('title')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['Diskon Avanza'], $titles(['q' => 'avanza']));
        $this->assertSame(['Diskon Avanza', 'Gratis Servis'], $titles(['status' => 'berjalan']));
        $this->assertSame(['Promo Natal'], $titles(['status' => 'terjadwal']));
        $this->assertSame(['Promo Kemerdekaan'], $titles(['status' => 'berakhir']));
        $this->assertSame(['Promo Dimatikan'], $titles(['status' => 'nonaktif']));
        $this->assertSame(['Diskon Avanza'], $titles(['jenis' => 'mobil']));
        $this->assertCount(4, $titles(['jenis' => 'umum']));
        $this->assertSame(['Gratis Servis'], $titles(['jenis' => 'umum', 'status' => 'berjalan']));
        $this->assertCount(5, $titles(['status' => 'ngawur', 'jenis' => 'x']));

        $this->actingAs($this->admin)->get(route('admin.promos.index', ['q' => 'tidak-ada']))
            ->assertSee('Promo tidak ditemukan');
    }

    public function test_pagination_sepuluh_per_halaman(): void
    {
        Promo::factory()->count(12)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.promos.index'));

        $this->assertCount(10, $response->viewData('promos'));
        $this->assertCount(2, $this->actingAs($this->admin)->get(route('admin.promos.index', ['page' => 2]))->viewData('promos'));
    }

    public function test_jumlah_query_daftar_promo_tidak_bertambah_seiring_data(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->admin)->get(route('admin.promos.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        Promo::factory()->forCar($this->car)->create();
        $queriesWithOnePromo = $count();

        foreach (Car::factory()->count(5)->create() as $car) {
            Promo::factory()->forCar($car)->create();
        }
        Promo::factory()->count(4)->create();

        $this->assertSame($queriesWithOnePromo, $count());
    }

    public function test_harga_akhir_memakai_diskon_promo_aktif_terbesar(): void
    {
        $load = fn () => Car::with('activePromos')->find($this->car->id);

        $this->assertSame(285_000_000, $load()->finalPrice());
        $this->assertFalse($load()->hasPromoPrice());

        Promo::factory()->forCar($this->car, 10_000_000)->create();
        Promo::factory()->forCar($this->car, 15_000_000)->create();
        Promo::factory()->forCar($this->car, 12_000_000)->create();

        $car = $load();
        $this->assertSame(15_000_000, $car->promoDiscount());
        $this->assertSame(270_000_000, $car->finalPrice());
        $this->assertTrue($car->hasPromoPrice());
        $this->assertSame(15_000_000, $car->bestActivePromo()->discount_amount);
    }

    public function test_promo_tidak_berjalan_dan_promo_umum_tidak_mengurangi_harga(): void
    {
        Promo::factory()->forCar($this->car, 5_000_000)->create();
        Promo::factory()->forCar($this->car, 50_000_000)->scheduled()->create();
        Promo::factory()->forCar($this->car, 40_000_000)->ended()->create();
        Promo::factory()->forCar($this->car, 30_000_000)->inactive()->create();
        Promo::factory()->create(['discount_amount' => 20_000_000]);
        Promo::factory()->forCar(Car::factory()->create(['price' => 500_000_000]), 60_000_000)->create();

        $this->assertSame(280_000_000, Car::with('activePromos')->find($this->car->id)->finalPrice());
    }

    public function test_diskon_yang_tidak_lagi_lebih_kecil_dari_harga_diabaikan(): void
    {
        Promo::factory()->forCar($this->car, 8_000_000)->create();
        Promo::factory()->forCar($this->car, 200_000_000)->create();
        $this->car->update(['price' => 150_000_000]);

        $car = Car::with('activePromos')->find($this->car->id);

        $this->assertSame(8_000_000, $car->promoDiscount());
        $this->assertSame(142_000_000, $car->finalPrice());
    }

    public function test_daftar_mobil_menampilkan_badge_promo_dan_harga_coret(): void
    {
        Promo::factory()->forCar($this->car, 15_000_000)->create();
        Car::factory()->create(['name' => 'Brio', 'price' => 199_000_000]);

        $this->actingAs($this->admin)->get(route('admin.cars.index'))
            ->assertOk()
            ->assertSeeInOrder(['Avanza', 'Promo', '<del', 'Rp 285.000.000', '</del>', 'Rp 270.000.000'], false)
            ->assertSee('Rp 199.000.000')
            ->assertDontSee('Rp 184.000.000');
    }

    public function test_jumlah_query_daftar_mobil_tidak_bertambah_dengan_promo(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->admin)->get(route('admin.cars.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        Promo::factory()->forCar($this->car)->create();
        $queriesWithOneCar = $count();

        foreach (Car::factory()->count(9)->create() as $car) {
            Promo::factory()->forCar($car)->count(2)->create();
        }

        $this->assertSame($queriesWithOneCar, $count());
    }

    public function test_seeder_promo_aman_dijalankan_ulang(): void
    {
        $this->seed(CarSeeder::class);
        $this->seed(PromoSeeder::class);
        $this->seed(PromoSeeder::class);

        $promos = Promo::with('car:id,slug')->get()->keyBy('slug');

        $this->assertCount(5, $promos);
        $this->assertEquals(['running' => 3, 'scheduled' => 1, 'ended' => 1], $promos->countBy('status')->all());
        $this->assertSame('toyota-avanza-1-5-g-cvt-2025', $promos['diskon-spesial-avanza']->car->slug);
        $this->assertSame('honda-hr-v-1-5-se-cvt-2025', $promos['cashback-hr-v']->car->slug);
        $this->assertSame('mitsubishi-xpander-cross-premium-cvt-2025', $promos['promo-xpander-akhir-tahun']->car->slug);
        $this->assertNull($promos['gratis-servis-3-tahun']->car_id);
        $this->assertNull($promos['promo-kemerdekaan']->discount_amount);
        $this->assertTrue($promos->every(fn (Promo $promo) => $promo->image === null));

        $avanza = Car::with('activePromos')->where('slug', 'toyota-avanza-1-5-g-cvt-2025')->sole();
        $this->assertSame(270_000_000, $avanza->finalPrice());
    }

    public function test_seeder_promo_melewati_promo_mobil_jika_mobil_belum_ada(): void
    {
        $this->seed(PromoSeeder::class);

        $this->assertSame(['gratis-servis-3-tahun', 'promo-kemerdekaan'], Promo::orderBy('slug')->pluck('slug')->all());
    }
}
