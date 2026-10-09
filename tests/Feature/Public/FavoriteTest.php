<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\Account\FavoriteController;
use App\Models\Brand;
use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Mobil favorit customer: tombol hati di kartu & detail, halaman Favorit Saya.
 */
class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create();
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Fairlady Z',
            'year' => 2003,
        ]);
    }

    // ---------- Simpan & hapus ----------

    public function test_customer_menyimpan_dan_menghapus_favorit(): void
    {
        $this->actingAs($this->customer)
            ->from(route('cars.show', $this->car))
            ->post(route('favorites.store', $this->car))
            ->assertRedirect(route('cars.show', $this->car))
            ->assertSessionHas('success', 'Fairlady Z 2003 disimpan ke favorit.');

        $this->assertDatabaseHas('favorites', ['user_id' => $this->customer->id, 'car_id' => $this->car->id]);

        $this->actingAs($this->customer)
            ->from(route('cars.show', $this->car))
            ->delete(route('favorites.destroy', $this->car))
            ->assertRedirect(route('cars.show', $this->car))
            ->assertSessionHas('success', 'Fairlady Z 2003 dihapus dari favorit.');

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_simpan_dua_kali_tidak_membuat_data_ganda(): void
    {
        $this->actingAs($this->customer)->post(route('favorites.store', $this->car));
        $this->actingAs($this->customer)->post(route('favorites.store', $this->car))
            ->assertSessionHas('status', 'Fairlady Z 2003 sudah ada di favorit Anda.');

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->post(route('favorites.store', $this->car))->assertRedirect(route('login'));
        $this->delete(route('favorites.destroy', $this->car))->assertRedirect(route('login'));
        $this->get(route('account.favorites.index'))->assertRedirect(route('login'));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_admin_tidak_bisa_memakai_favorit(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('favorites.store', $this->car))->assertForbidden();
        $this->actingAs($admin)->get(route('account.favorites.index'))->assertForbidden();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_mobil_nonaktif_tidak_bisa_disimpan_tetapi_bisa_dihapus(): void
    {
        $inactive = Car::factory()->inactive()->create();

        $this->actingAs($this->customer)->post(route('favorites.store', $inactive))->assertNotFound();
        $this->assertDatabaseCount('favorites', 0);

        $this->customer->favoriteCars()->attach($inactive);

        $this->actingAs($this->customer)->delete(route('favorites.destroy', $inactive))->assertSessionHas('success');
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_batas_maksimal_favorit(): void
    {
        $this->customer->favoriteCars()->attach(Car::factory()->count(FavoriteController::MAX_FAVORITES)->create());

        $this->actingAs($this->customer)->post(route('favorites.store', $this->car))
            ->assertSessionHas('error', 'Favorit sudah penuh (maks. 50 mobil). Hapus salah satu terlebih dahulu.');

        $this->assertSame(FavoriteController::MAX_FAVORITES, $this->customer->favoriteCars()->count());
    }

    public function test_mobil_dihapus_admin_ikut_terhapus_dari_favorit(): void
    {
        $this->customer->favoriteCars()->attach($this->car);

        $this->car->delete();

        $this->assertDatabaseCount('favorites', 0);
    }

    // ---------- Tombol hati ----------

    public function test_tombol_hati_di_kartu_dan_detail_sesuai_status(): void
    {
        $other = Car::factory()->create();
        $this->customer->favoriteCars()->attach($this->car);

        $catalog = $this->actingAs($this->customer)->get(route('cars.index'))->assertOk();
        $catalog->assertSee('action="'.route('favorites.destroy', $this->car).'"', false)
            ->assertSee('action="'.route('favorites.store', $other).'"', false)
            ->assertSee('aria-pressed="true"', false)
            ->assertSee('aria-pressed="false"', false);

        $this->actingAs($this->customer)->get(route('cars.show', $this->car))
            ->assertSee('Hapus dari Favorit');
        $this->actingAs($this->customer)->get(route('cars.show', $other))
            ->assertSee('Simpan ke Favorit');
    }

    public function test_tamu_melihat_tautan_login_dan_admin_tidak_melihat_tombol(): void
    {
        $this->get(route('cars.show', $this->car))
            ->assertSee('title="Masuk untuk menyimpan favorit"', false)
            ->assertDontSee('action="'.route('favorites.store', $this->car).'"', false);

        $this->actingAs(User::factory()->admin()->create())->get(route('cars.show', $this->car))
            ->assertDontSee('car-card-favorite', false)
            ->assertDontSee('Simpan ke Favorit');
    }

    public function test_status_favorit_di_katalog_tanpa_query_tambahan_per_mobil(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->customer)->get(route('cars.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $this->customer->favoriteCars()->attach($this->car);
        $queriesWithOneCar = $count();

        $this->customer->favoriteCars()->attach(Car::factory()->count(5)->create());
        $this->customer->forgetFavoriteCarIds();

        $this->assertSame($queriesWithOneCar, $count());
    }

    // ---------- Halaman Favorit Saya ----------

    public function test_halaman_favorit_hanya_menampilkan_milik_sendiri(): void
    {
        $this->actingAs($this->customer)->get(route('account.favorites.index'))
            ->assertOk()
            ->assertSee('Belum ada mobil favorit');

        $mine = Car::factory()->create(['name' => 'Silvia S15']);
        $others = Car::factory()->create(['name' => 'Laurel C33']);
        $this->customer->favoriteCars()->attach($mine);
        User::factory()->create()->favoriteCars()->attach($others);

        $this->actingAs($this->customer)->get(route('account.favorites.index'))
            ->assertOk()
            ->assertSee('Silvia S15')
            ->assertDontSee('Laurel C33')
            ->assertSee('1 dari 50 mobil');
    }

    public function test_mobil_favorit_yang_dinonaktifkan_tampil_tidak_tersedia(): void
    {
        $car = Car::factory()->inactive()->create(['name' => 'Cefiro A31']);
        $this->customer->favoriteCars()->attach($car);

        $this->actingAs($this->customer)->get(route('account.favorites.index'))
            ->assertOk()
            ->assertSee('Cefiro A31')
            ->assertSee('Tidak tersedia')
            ->assertDontSee('href="'.route('cars.show', $car).'"', false)
            ->assertSee('action="'.route('favorites.destroy', $car).'"', false);
    }

    public function test_menu_dan_profil_menampilkan_favorit(): void
    {
        $this->customer->favoriteCars()->attach($this->car);

        $this->actingAs($this->customer)->get(route('home'))
            ->assertSee('href="'.route('account.favorites.index').'"', false);

        $this->actingAs($this->customer)->get(route('account.profile'))
            ->assertOk()
            ->assertSee('Favorit Saya');
    }
}
