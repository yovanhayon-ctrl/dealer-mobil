<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Car;
use App\Models\User;
use Database\Seeders\BrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->admin()->create();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $brand = Brand::factory()->create();

        $this->get('/admin/merek')->assertRedirect(route('login'));
        $this->get('/admin/merek/tambah')->assertRedirect(route('login'));
        $this->post('/admin/merek', ['name' => 'Toyota'])->assertRedirect(route('login'));
        $this->get("/admin/merek/{$brand->id}/ubah")->assertRedirect(route('login'));
        $this->put("/admin/merek/{$brand->id}", ['name' => 'X'])->assertRedirect(route('login'));
        $this->delete("/admin/merek/{$brand->id}")->assertRedirect(route('login'));

        $this->assertModelExists($brand);
    }

    public function test_customer_mendapat_403(): void
    {
        $customer = User::factory()->create();
        $brand = Brand::factory()->create();

        $this->actingAs($customer);
        $this->get('/admin/merek')->assertForbidden();
        $this->get('/admin/merek/tambah')->assertForbidden();
        $this->post('/admin/merek', ['name' => 'Toyota'])->assertForbidden();
        $this->get("/admin/merek/{$brand->id}/ubah")->assertForbidden();
        $this->put("/admin/merek/{$brand->id}", ['name' => 'X'])->assertForbidden();
        $this->delete("/admin/merek/{$brand->id}")->assertForbidden();

        $this->assertModelExists($brand);
        $this->assertDatabaseMissing('brands', ['name' => 'Toyota']);
    }

    public function test_daftar_menampilkan_jumlah_mobil_pencarian_dan_menu_sidebar(): void
    {
        $toyota = Brand::factory()->create(['name' => 'Toyota']);
        Brand::factory()->create(['name' => 'Honda']);
        Car::factory()->count(3)->recycle($toyota)->create();

        $this->actingAs($this->admin)->get(route('admin.brands.index'))
            ->assertOk()
            ->assertSee('Toyota')
            ->assertSee('Honda')
            ->assertViewHas('brands', fn ($brands) => $brands->firstWhere('name', 'Toyota')->cars_count === 3)
            ->assertSee('class="nav-link active" href="'.route('admin.brands.index').'"', false)
            ->assertSee('href="'.route('admin.categories.index').'"', false);

        $this->actingAs($this->admin)->get(route('admin.brands.index', ['q' => 'toy']))
            ->assertOk()
            ->assertSee('Toyota')
            ->assertDontSee('Honda');
    }

    public function test_daftar_dipaginasi_sepuluh_per_halaman(): void
    {
        Brand::factory()->count(12)->create();

        $this->actingAs($this->admin)->get(route('admin.brands.index'))
            ->assertViewHas('brands', fn ($brands) => $brands->count() === 10 && $brands->total() === 12)
            ->assertSeeText('Menampilkan 1 sampai 10 dari 12 data')
            ->assertSee('aria-label="Halaman 2"', false);

        $this->actingAs($this->admin)->get(route('admin.brands.index', ['page' => 2]))
            ->assertViewHas('brands', fn ($brands) => $brands->count() === 2);
    }

    public function test_empty_state_saat_kosong_dan_saat_pencarian_tidak_cocok(): void
    {
        $this->actingAs($this->admin)->get(route('admin.brands.index'))
            ->assertSee('Belum ada merek');

        Brand::factory()->create(['name' => 'Toyota']);

        $this->actingAs($this->admin)->get(route('admin.brands.index', ['q' => 'zzz']))
            ->assertSee('Merek tidak ditemukan');
    }

    public function test_admin_bisa_menambah_merek_dengan_logo(): void
    {
        $this->actingAs($this->admin)->get(route('admin.brands.create'))->assertOk();

        $this->actingAs($this->admin)->post(route('admin.brands.store'), [
            'name' => '  Toyota  ',
            'logo' => UploadedFile::fake()->image('toyota.png', 200, 200),
        ])
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success', 'Merek "Toyota" berhasil ditambahkan.');

        $brand = Brand::firstWhere('name', 'Toyota');
        $this->assertSame('toyota', $brand->slug);
        $this->assertNotNull($brand->logo);
        Storage::disk('public')->assertExists($brand->logo);
    }

    public function test_admin_bisa_menambah_merek_tanpa_logo(): void
    {
        $this->actingAs($this->admin)->post(route('admin.brands.store'), ['name' => 'Wuling'])
            ->assertRedirect(route('admin.brands.index'));

        $this->assertDatabaseHas('brands', ['name' => 'Wuling', 'slug' => 'wuling', 'logo' => null]);
    }

    public function test_validasi_nama_wajib_dan_unik_tanpa_beda_huruf(): void
    {
        Brand::factory()->create(['name' => 'Toyota']);

        $this->actingAs($this->admin)->from(route('admin.brands.create'))
            ->post(route('admin.brands.store'), ['name' => ''])
            ->assertRedirect(route('admin.brands.create'))
            ->assertSessionHasErrors(['name' => 'Nama merek wajib diisi.']);

        $this->actingAs($this->admin)->post(route('admin.brands.store'), ['name' => 'toyota'])
            ->assertSessionHasErrors(['name' => 'Nama merek sudah terdaftar.']);

        $this->assertSame(1, Brand::count());
    }

    public function test_logo_tidak_valid_ditolak(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $pdf = UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf');
        $tooBig = UploadedFile::fake()->image('logo.png')->size(1025);

        foreach ([$svg, $pdf] as $file) {
            $this->actingAs($this->admin)->post(route('admin.brands.store'), ['name' => 'Honda', 'logo' => $file])
                ->assertSessionHasErrors('logo');
        }

        $this->actingAs($this->admin)->post(route('admin.brands.store'), ['name' => 'Honda', 'logo' => $tooBig])
            ->assertSessionHasErrors(['logo' => 'Ukuran logo maksimal 1 MB.']);

        $this->assertDatabaseMissing('brands', ['name' => 'Honda']);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_slug_tetap_unik_untuk_nama_yang_mirip(): void
    {
        $this->actingAs($this->admin)->post(route('admin.brands.store'), ['name' => 'Mercedes Benz']);
        $this->actingAs($this->admin)->post(route('admin.brands.store'), ['name' => 'Mercedes-Benz']);

        $this->assertSame(
            ['mercedes-benz', 'mercedes-benz-2'],
            Brand::orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_admin_bisa_mengubah_nama_dan_mengganti_logo(): void
    {
        $oldLogo = UploadedFile::fake()->image('lama.png')->store('brands', 'public');
        $brand = Brand::factory()->create(['name' => 'Toyota', 'slug' => 'toyota', 'logo' => $oldLogo]);

        $this->actingAs($this->admin)->get(route('admin.brands.edit', $brand))
            ->assertOk()
            ->assertSee('value="Toyota"', false);

        $this->actingAs($this->admin)->put(route('admin.brands.update', $brand), [
            'name' => 'Toyota Astra',
            'logo' => UploadedFile::fake()->image('baru.webp'),
        ])
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success');

        $brand->refresh();
        $this->assertSame('Toyota Astra', $brand->name);
        $this->assertSame('toyota-astra', $brand->slug);
        $this->assertNotSame($oldLogo, $brand->logo);
        Storage::disk('public')->assertMissing($oldLogo);
        Storage::disk('public')->assertExists($brand->logo);
    }

    public function test_simpan_dengan_nama_sendiri_bukan_duplikat_dan_logo_bisa_dihapus(): void
    {
        $oldLogo = UploadedFile::fake()->image('lama.png')->store('brands', 'public');
        $brand = Brand::factory()->create(['name' => 'Honda', 'slug' => 'honda', 'logo' => $oldLogo]);

        $this->actingAs($this->admin)->put(route('admin.brands.update', $brand), [
            'name' => 'Honda',
            'remove_logo' => '1',
        ])->assertSessionHasNoErrors();

        $brand->refresh();
        $this->assertSame('honda', $brand->slug);
        $this->assertNull($brand->logo);
        Storage::disk('public')->assertMissing($oldLogo);
    }

    public function test_edit_tanpa_file_baru_mempertahankan_logo(): void
    {
        $logo = UploadedFile::fake()->image('logo.png')->store('brands', 'public');
        $brand = Brand::factory()->create(['logo' => $logo]);

        $this->actingAs($this->admin)->put(route('admin.brands.update', $brand), ['name' => 'Suzuki'])
            ->assertSessionHasNoErrors();

        $this->assertSame($logo, $brand->refresh()->logo);
        Storage::disk('public')->assertExists($logo);
    }

    public function test_admin_bisa_menghapus_merek_yang_tidak_dipakai_beserta_logonya(): void
    {
        $logo = UploadedFile::fake()->image('logo.png')->store('brands', 'public');
        $brand = Brand::factory()->create(['name' => 'Wuling', 'logo' => $logo]);

        $this->actingAs($this->admin)->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('success', 'Merek "Wuling" berhasil dihapus.');

        $this->assertModelMissing($brand);
        Storage::disk('public')->assertMissing($logo);
    }

    public function test_hapus_merek_yang_masih_dipakai_mobil_ditolak(): void
    {
        $brand = Brand::factory()->create(['name' => 'Toyota']);
        Car::factory()->count(2)->recycle($brand)->create();

        $this->actingAs($this->admin)->get(route('admin.brands.index'))
            ->assertSee('Masih dipakai 2 mobil');

        $this->actingAs($this->admin)->delete(route('admin.brands.destroy', $brand))
            ->assertRedirect(route('admin.brands.index'))
            ->assertSessionHas('error', 'Merek "Toyota" tidak bisa dihapus karena masih dipakai oleh 2 mobil.');

        $this->assertModelExists($brand);
    }

    public function test_seeder_merek_aman_dijalankan_ulang(): void
    {
        $this->seed(BrandSeeder::class);
        $this->seed(BrandSeeder::class);

        $this->assertSame(7, Brand::count());
        $this->assertDatabaseHas('brands', ['name' => 'Toyota', 'slug' => 'toyota', 'logo' => null]);
    }
}
