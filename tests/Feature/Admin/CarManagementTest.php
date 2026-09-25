<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\Promo;
use App\Models\PurchaseRequest;
use App\Models\TestDrive;
use App\Models\User;
use Database\Seeders\CarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CarManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Brand $toyota;

    private Category $mpv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->toyota = Brand::factory()->create(['name' => 'Toyota', 'slug' => 'toyota']);
        $this->mpv = Category::factory()->create(['name' => 'MPV', 'slug' => 'mpv']);
    }

    /**
     * Data form mobil yang valid; timpa sebagian lewat $overrides.
     *
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return [
            'brand_id' => $this->toyota->id,
            'category_id' => $this->mpv->id,
            'name' => 'Avanza',
            'vehicle_condition' => 'baru',
            'year' => 2025,
            'mileage' => '',
            'price' => '285.000.000',
            'transmission' => 'automatic',
            'fuel_type' => 'bensin',
            'engine_cc' => 1496,
            'seats' => 7,
            'color' => 'Putih',
            'stock' => 5,
            'description' => 'MPV keluarga.',
            'is_active' => '1',
            ...$overrides,
        ];
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $car = Car::factory()->create();

        $this->get('/admin/mobil')->assertRedirect(route('login'));
        $this->get('/admin/mobil/tambah')->assertRedirect(route('login'));
        $this->post('/admin/mobil', $this->validData())->assertRedirect(route('login'));
        $this->get("/admin/mobil/{$car->id}/ubah")->assertRedirect(route('login'));
        $this->put("/admin/mobil/{$car->id}", $this->validData())->assertRedirect(route('login'));
        $this->patch("/admin/mobil/{$car->id}/status")->assertRedirect(route('login'));
        $this->delete("/admin/mobil/{$car->id}")->assertRedirect(route('login'));

        $this->assertModelExists($car);
        $this->assertTrue($car->fresh()->is_active);
    }

    public function test_customer_mendapat_403(): void
    {
        $car = Car::factory()->create();

        $this->actingAs(User::factory()->create());
        $this->get('/admin/mobil')->assertForbidden();
        $this->get('/admin/mobil/tambah')->assertForbidden();
        $this->post('/admin/mobil', $this->validData())->assertForbidden();
        $this->get("/admin/mobil/{$car->id}/ubah")->assertForbidden();
        $this->put("/admin/mobil/{$car->id}", $this->validData())->assertForbidden();
        $this->patch("/admin/mobil/{$car->id}/status")->assertForbidden();
        $this->delete("/admin/mobil/{$car->id}")->assertForbidden();

        $this->assertSame(1, Car::count());
        $this->assertTrue($car->fresh()->is_active);
    }

    public function test_admin_bisa_menambah_mobil_baru(): void
    {
        $this->actingAs($this->admin)->get(route('admin.cars.create'))
            ->assertOk()
            ->assertSee('Otomatis')
            ->assertSee('Listrik');

        $this->actingAs($this->admin)
            ->post(route('admin.cars.store'), $this->validData(['mileage' => '50.000']))
            ->assertRedirect(route('admin.cars.index'))
            ->assertSessionHas('success', 'Mobil "Avanza" berhasil ditambahkan.');

        $car = Car::firstWhere('name', 'Avanza');
        $this->assertSame('toyota-avanza-2025', $car->slug);
        $this->assertSame(285_000_000, $car->price);
        $this->assertSame(0, $car->mileage, 'Mobil baru selalu 0 km.');
        $this->assertTrue($car->is_active);
    }

    public function test_aturan_kilometer_mobil_bekas(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.cars.store'), $this->validData(['vehicle_condition' => 'bekas', 'mileage' => '']))
            ->assertSessionHasErrors(['mileage' => 'Kilometer wajib diisi untuk mobil bekas.']);

        $this->actingAs($this->admin)
            ->post(route('admin.cars.store'), $this->validData(['vehicle_condition' => 'bekas', 'mileage' => '0']))
            ->assertSessionHasErrors(['mileage' => 'Kilometer mobil bekas minimal 1 km.']);

        $this->assertSame(0, Car::count());

        $this->actingAs($this->admin)
            ->post(route('admin.cars.store'), $this->validData(['vehicle_condition' => 'bekas', 'mileage' => '45.000']))
            ->assertSessionHasNoErrors();

        $this->assertSame(45_000, Car::sole()->mileage);
    }

    public function test_validasi_field_lain_ditolak(): void
    {
        $cases = [
            'brand_id' => ['brand_id' => 9999],
            'category_id' => ['category_id' => 9999],
            'name' => ['name' => '   '],
            'vehicle_condition' => ['vehicle_condition' => 'rusak'],
            'year' => ['year' => 1989],
            'price' => ['price' => '0'],
            'transmission' => ['transmission' => 'cvt'],
            'fuel_type' => ['fuel_type' => 'avtur'],
            'seats' => ['seats' => 10],
            'stock' => ['stock' => -1],
            'engine_cc' => ['engine_cc' => 100],
        ];

        foreach ($cases as $field => $override) {
            $this->actingAs($this->admin)
                ->post(route('admin.cars.store'), $this->validData($override))
                ->assertSessionHasErrors($field);
        }

        $this->actingAs($this->admin)
            ->post(route('admin.cars.store'), $this->validData(['year' => now()->year + 2]))
            ->assertSessionHasErrors('year');

        $this->actingAs($this->admin)
            ->post(route('admin.cars.store'), $this->validData(['seats' => 1]))
            ->assertSessionHasErrors('seats');

        $this->actingAs($this->admin)
            ->post(route('admin.cars.store'), $this->validData(['brand_id' => '', 'name' => '']))
            ->assertSessionHasErrors([
                'brand_id' => 'Merek wajib diisi.',
                'name' => 'Nama mobil wajib diisi.',
            ]);

        $this->assertSame(0, Car::count());
    }

    public function test_slug_unik_saat_tambah_mobil_yang_sama(): void
    {
        $this->actingAs($this->admin)->post(route('admin.cars.store'), $this->validData());
        $this->actingAs($this->admin)->post(route('admin.cars.store'), $this->validData());

        $this->assertSame(
            ['toyota-avanza-2025', 'toyota-avanza-2025-2'],
            Car::orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_edit_mengubah_data_tetapi_slug_tidak_berubah(): void
    {
        $this->actingAs($this->admin)->post(route('admin.cars.store'), $this->validData());
        $car = Car::sole();
        $honda = Brand::factory()->create(['name' => 'Honda']);

        $this->actingAs($this->admin)->get(route('admin.cars.edit', $car))
            ->assertOk()
            ->assertSee('value="Avanza"', false)
            ->assertSee('value="285.000.000"', false);

        // Merek, nama, dan tahun diubah; checkbox aktif tidak dikirim.
        $data = $this->validData([
            'brand_id' => $honda->id,
            'name' => 'Mobilio',
            'year' => 2024,
            'vehicle_condition' => 'bekas',
            'mileage' => '12.500',
            'price' => '199000000',
        ]);
        unset($data['is_active']);

        $this->actingAs($this->admin)->put(route('admin.cars.update', $car), $data)
            ->assertRedirect(route('admin.cars.index'))
            ->assertSessionHas('success', 'Mobil "Mobilio" berhasil diperbarui.');

        $car->refresh();
        $this->assertSame('Mobilio', $car->name);
        $this->assertSame($honda->id, $car->brand_id);
        $this->assertSame(2024, $car->year);
        $this->assertSame(12_500, $car->mileage);
        $this->assertSame(199_000_000, $car->price);
        $this->assertFalse($car->is_active);
        $this->assertSame('toyota-avanza-2025', $car->slug, 'Slug mobil tidak boleh berubah saat edit.');
    }

    public function test_ubah_ke_kondisi_baru_mereset_kilometer(): void
    {
        $car = Car::factory()->used()->create(['mileage' => 30_000]);

        $this->actingAs($this->admin)
            ->put(route('admin.cars.update', $car), $this->validData(['vehicle_condition' => 'baru', 'mileage' => '30.000']))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $car->fresh()->mileage);
    }

    public function test_daftar_menampilkan_data_label_dan_format_rupiah(): void
    {
        Car::factory()->recycle([$this->toyota, $this->mpv])->create([
            'name' => 'Avanza', 'year' => 2025, 'price' => 285_000_000, 'transmission' => 'automatic', 'stock' => 0,
        ]);

        $this->actingAs($this->admin)->get(route('admin.cars.index'))
            ->assertOk()
            ->assertSee('Avanza')
            ->assertSee('Rp 285.000.000')
            ->assertSee('Otomatis')
            ->assertSee('Stok Habis')
            ->assertSee('Toyota')
            ->assertSee('MPV')
            ->assertSee('class="nav-link active" href="'.route('admin.cars.index').'"', false);
    }

    public function test_filter_dan_pencarian(): void
    {
        $honda = Brand::factory()->create(['name' => 'Honda']);
        $suv = Category::factory()->create(['name' => 'SUV']);

        $avanza = Car::factory()->recycle([$this->toyota, $this->mpv])->create(['name' => 'Avanza']);
        $hrv = Car::factory()->recycle([$honda, $suv])->create(['name' => 'HR-V']);
        $civic = Car::factory()->used()->recycle([$honda, $this->mpv])->create(['name' => 'Civic', 'stock' => 0]);
        $sigra = Car::factory()->inactive()->recycle([$this->toyota, $suv])->create(['name' => 'Sigra']);

        $expectations = [
            ['q' => 'avan', [$avanza]],
            ['merek' => $honda->id, [$hrv, $civic]],
            ['kategori' => $suv->id, [$hrv, $sigra]],
            ['kondisi' => 'bekas', [$civic]],
            ['status' => 'nonaktif', [$sigra]],
            ['status' => 'aktif', [$avanza, $hrv, $civic]],
            ['stok' => 'habis', [$civic]],
            ['merek' => $honda->id, 'kondisi' => 'baru', [$hrv]],
            // Nilai tidak dikenal diabaikan.
            ['kondisi' => 'rusak', 'merek' => 'abc', 'urut' => 'acak', [$avanza, $hrv, $civic, $sigra]],
        ];

        foreach ($expectations as $case) {
            $expected = collect(array_pop($case))->pluck('id')->sort()->values()->all();

            $this->actingAs($this->admin)->get(route('admin.cars.index', $case))
                ->assertOk()
                ->assertViewHas('cars', fn ($cars) => $cars->pluck('id')->sort()->values()->all() === $expected);
        }

        $this->actingAs($this->admin)->get(route('admin.cars.index', ['q' => 'tidak-ada']))
            ->assertSee('Mobil tidak ditemukan');
    }

    public function test_urutan_harga_dan_tahun(): void
    {
        $cheap = Car::factory()->create(['price' => 150_000_000, 'year' => 2025]);
        $mid = Car::factory()->create(['price' => 300_000_000, 'year' => 2020]);
        $expensive = Car::factory()->create(['price' => 800_000_000, 'year' => 2023]);

        $orders = [
            'harga_termurah' => [$cheap, $mid, $expensive],
            'harga_termahal' => [$expensive, $mid, $cheap],
            'tahun_terbaru' => [$cheap, $expensive, $mid],
            'tahun_terlama' => [$mid, $expensive, $cheap],
            'terbaru' => [$expensive, $mid, $cheap],
        ];

        foreach ($orders as $sort => $expected) {
            $this->actingAs($this->admin)->get(route('admin.cars.index', ['urut' => $sort]))
                ->assertViewHas('cars', fn ($cars) => $cars->pluck('id')->all() === collect($expected)->pluck('id')->all());
        }
    }

    public function test_pagination_sepuluh_per_halaman_dengan_teks_indonesia(): void
    {
        Car::factory()->count(12)->create();

        $this->actingAs($this->admin)->get(route('admin.cars.index'))
            ->assertViewHas('cars', fn ($cars) => $cars->count() === 10)
            ->assertSeeText('Menampilkan 1 sampai 10 dari 12 data')
            ->assertSee('aria-label="Halaman 2"', false)
            ->assertDontSee('Showing');

        $this->actingAs($this->admin)->get(route('admin.cars.index', ['page' => 2, 'kondisi' => 'baru']))
            ->assertViewHas('cars', fn ($cars) => $cars->count() === 2)
            ->assertSeeText('Menampilkan 11 sampai 12 dari 12 data');
    }

    public function test_jumlah_query_daftar_tidak_bertambah_seiring_data(): void
    {
        Car::factory()->create();
        $queriesWithOneCar = $this->countIndexQueries();

        Car::factory()->count(9)->create();
        $queriesWithTenCars = $this->countIndexQueries();

        $this->assertSame($queriesWithOneCar, $queriesWithTenCars);
    }

    public function test_toggle_aktif_dan_nonaktif_kembali_ke_halaman_asal(): void
    {
        $car = Car::factory()->create(['name' => 'Avanza']);
        $from = route('admin.cars.index', ['kondisi' => 'baru', 'page' => 1]);

        $this->actingAs($this->admin)->from($from)->patch(route('admin.cars.toggle-active', $car))
            ->assertRedirect($from)
            ->assertSessionHas('success', 'Mobil "Avanza" dinonaktifkan.');
        $this->assertFalse($car->fresh()->is_active);

        $this->actingAs($this->admin)->from($from)->patch(route('admin.cars.toggle-active', $car))
            ->assertRedirect($from)
            ->assertSessionHas('success', 'Mobil "Avanza" diaktifkan.');
        $this->assertTrue($car->fresh()->is_active);
    }

    public function test_admin_bisa_menghapus_mobil_tanpa_relasi(): void
    {
        $car = Car::factory()->create(['name' => 'Avanza']);

        $this->actingAs($this->admin)->delete(route('admin.cars.destroy', $car))
            ->assertRedirect(route('admin.cars.index'))
            ->assertSessionHas('success', 'Mobil "Avanza" berhasil dihapus.');

        $this->assertModelMissing($car);
    }

    public function test_hapus_ditolak_jika_mobil_punya_test_drive_atau_pengajuan(): void
    {
        $car = Car::factory()->create(['name' => 'Avanza', 'year' => 2025]);
        TestDrive::factory()->count(2)->recycle($car)->create();
        PurchaseRequest::factory()->recycle($car)->create();

        $this->actingAs($this->admin)->get(route('admin.cars.index'))
            ->assertSee('Sudah memiliki 2 test drive dan 1 pengajuan');

        $this->actingAs($this->admin)->delete(route('admin.cars.destroy', $car))
            ->assertRedirect(route('admin.cars.index'))
            ->assertSessionHas('error', 'Mobil "Avanza 2025" tidak bisa dihapus karena sudah memiliki 2 test drive dan 1 pengajuan. '
                .'Nonaktifkan mobil ini agar tidak tampil di katalog.');

        $this->assertModelExists($car);
    }

    public function test_hapus_ditolak_jika_mobil_punya_promo(): void
    {
        $car = Car::factory()->create(['name' => 'Xenia', 'year' => 2025]);
        Promo::create([
            'car_id' => $car->id,
            'title' => 'Diskon Xenia',
            'slug' => 'diskon-xenia',
            'discount_amount' => 10_000_000,
            'start_date' => today(),
            'end_date' => today()->addMonth(),
        ]);

        $this->actingAs($this->admin)->delete(route('admin.cars.destroy', $car))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'sudah memiliki 1 promo'));

        $this->assertModelExists($car);
        $this->assertDatabaseHas('promos', ['slug' => 'diskon-xenia', 'car_id' => $car->id]);
    }

    public function test_seeder_mobil_aman_dijalankan_ulang(): void
    {
        $this->seed(CarSeeder::class);
        $this->seed(CarSeeder::class);

        $this->assertSame(15, Car::count());
        $this->assertSame(2, Car::where('stock', 0)->count());
        $this->assertSame(1, Car::where('is_active', false)->count());
        $this->assertTrue(Car::where('vehicle_condition', 'bekas')->where('mileage', '<', 1)->doesntExist());
        $this->assertTrue(Car::where('vehicle_condition', 'baru')->where('mileage', '>', 0)->doesntExist());
        $this->assertDatabaseHas('cars', ['slug' => 'toyota-avanza-1-5-g-cvt-2025', 'price' => 285_000_000]);
    }

    private function countIndexQueries(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->admin)->get(route('admin.cars.index'))->assertOk();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
