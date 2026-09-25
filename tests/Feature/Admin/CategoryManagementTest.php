<?php

namespace Tests\Feature\Admin;

use App\Models\Car;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $category = Category::factory()->create();

        $this->get('/admin/kategori')->assertRedirect(route('login'));
        $this->get('/admin/kategori/tambah')->assertRedirect(route('login'));
        $this->post('/admin/kategori', ['name' => 'SUV'])->assertRedirect(route('login'));
        $this->get("/admin/kategori/{$category->id}/ubah")->assertRedirect(route('login'));
        $this->put("/admin/kategori/{$category->id}", ['name' => 'X'])->assertRedirect(route('login'));
        $this->delete("/admin/kategori/{$category->id}")->assertRedirect(route('login'));

        $this->assertModelExists($category);
    }

    public function test_customer_mendapat_403(): void
    {
        $customer = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($customer);
        $this->get('/admin/kategori')->assertForbidden();
        $this->get('/admin/kategori/tambah')->assertForbidden();
        $this->post('/admin/kategori', ['name' => 'SUV'])->assertForbidden();
        $this->get("/admin/kategori/{$category->id}/ubah")->assertForbidden();
        $this->put("/admin/kategori/{$category->id}", ['name' => 'X'])->assertForbidden();
        $this->delete("/admin/kategori/{$category->id}")->assertForbidden();

        $this->assertModelExists($category);
        $this->assertDatabaseMissing('categories', ['name' => 'SUV']);
    }

    public function test_daftar_menampilkan_jumlah_mobil_dan_pencarian(): void
    {
        $suv = Category::factory()->create(['name' => 'SUV']);
        Category::factory()->create(['name' => 'Sedan']);
        Car::factory()->count(2)->recycle($suv)->create();

        $this->actingAs($this->admin)->get(route('admin.categories.index'))
            ->assertOk()
            ->assertViewHas('categories', fn ($categories) => $categories->firstWhere('name', 'SUV')->cars_count === 2)
            ->assertSee('class="nav-link active" href="'.route('admin.categories.index').'"', false);

        $this->actingAs($this->admin)->get(route('admin.categories.index', ['q' => 'sed']))
            ->assertSee('Sedan')
            ->assertDontSee('SUV');
    }

    public function test_daftar_dipaginasi_dan_empty_state(): void
    {
        $this->actingAs($this->admin)->get(route('admin.categories.index'))
            ->assertSee('Belum ada kategori');

        Category::factory()->count(11)->create();

        $this->actingAs($this->admin)->get(route('admin.categories.index'))
            ->assertViewHas('categories', fn ($categories) => $categories->count() === 10 && $categories->total() === 11)
            ->assertSeeText('Menampilkan 1 sampai 10 dari 11 data')
            ->assertSee('aria-label="Halaman 2"', false);

        $this->actingAs($this->admin)->get(route('admin.categories.index', ['q' => 'zzz-tidak-ada']))
            ->assertSee('Kategori tidak ditemukan');
    }

    public function test_admin_bisa_menambah_kategori(): void
    {
        $this->actingAs($this->admin)->get(route('admin.categories.create'))->assertOk();

        $this->actingAs($this->admin)->post(route('admin.categories.store'), ['name' => 'Hatchback'])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success', 'Kategori "Hatchback" berhasil ditambahkan.');

        $this->assertDatabaseHas('categories', ['name' => 'Hatchback', 'slug' => 'hatchback']);
    }

    public function test_validasi_nama_wajib_dan_unik_tanpa_beda_huruf(): void
    {
        Category::factory()->create(['name' => 'SUV']);

        $this->actingAs($this->admin)->post(route('admin.categories.store'), ['name' => '   '])
            ->assertSessionHasErrors(['name' => 'Nama kategori wajib diisi.']);

        $this->actingAs($this->admin)->post(route('admin.categories.store'), ['name' => 'suv'])
            ->assertSessionHasErrors(['name' => 'Nama kategori sudah terdaftar.']);

        $this->assertSame(1, Category::count());
    }

    public function test_slug_tetap_unik_untuk_nama_yang_mirip(): void
    {
        $this->actingAs($this->admin)->post(route('admin.categories.store'), ['name' => 'Pick Up']);
        $this->actingAs($this->admin)->post(route('admin.categories.store'), ['name' => 'Pick-Up']);

        $this->assertSame(['pick-up', 'pick-up-2'], Category::orderBy('id')->pluck('slug')->all());
    }

    public function test_admin_bisa_mengubah_kategori(): void
    {
        $category = Category::factory()->create(['name' => 'Pickup', 'slug' => 'pickup']);

        $this->actingAs($this->admin)->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee('value="Pickup"', false);

        // Nama sendiri tidak dianggap duplikat.
        $this->actingAs($this->admin)->put(route('admin.categories.update', $category), ['name' => 'Pickup'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->put(route('admin.categories.update', $category), ['name' => 'Double Cabin'])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success', 'Kategori "Double Cabin" berhasil diperbarui.');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Double Cabin', 'slug' => 'double-cabin']);
    }

    public function test_admin_bisa_menghapus_kategori_yang_tidak_dipakai(): void
    {
        $category = Category::factory()->create(['name' => 'LCGC']);

        $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success', 'Kategori "LCGC" berhasil dihapus.');

        $this->assertModelMissing($category);
    }

    public function test_hapus_kategori_yang_masih_dipakai_mobil_ditolak(): void
    {
        $category = Category::factory()->create(['name' => 'MPV']);
        Car::factory()->recycle($category)->create();

        $this->actingAs($this->admin)->get(route('admin.categories.index'))
            ->assertSee('Masih dipakai 1 mobil');

        $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error', 'Kategori "MPV" tidak bisa dihapus karena masih dipakai oleh 1 mobil.');

        $this->assertModelExists($category);
    }

    public function test_seeder_kategori_aman_dijalankan_ulang(): void
    {
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);

        $this->assertSame(6, Category::count());
        $this->assertDatabaseHas('categories', ['name' => 'LCGC', 'slug' => 'lcgc']);
    }
}
