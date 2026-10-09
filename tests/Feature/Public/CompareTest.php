<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Promo;
use App\Models\User;
use App\Support\CarComparison;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bandingkan mobil: pilihan di session (tamu boleh), maks. 3 mobil, tabel spesifikasi berdampingan.
 */
class CompareTest extends TestCase
{
    use RefreshDatabase;

    private Brand $nissan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nissan = Brand::factory()->create(['name' => 'Nissan']);
    }

    private function car(array $attributes = []): Car
    {
        return Car::factory()->create(['brand_id' => $this->nissan->id, ...$attributes]);
    }

    // ---------- Pilih / batal ----------

    public function test_tamu_bisa_menambah_dan_menghapus_mobil(): void
    {
        $car = $this->car(['name' => 'Silvia S15', 'year' => 2001]);

        $this->from(route('cars.index'))
            ->post(route('compare.store', $car))
            ->assertRedirect(route('cars.index'))
            ->assertSessionHas('success', 'Silvia S15 2001 ditambahkan ke perbandingan.')
            ->assertSessionHas('compare.cars', [$car->id]);

        $this->from(route('cars.index'))
            ->delete(route('compare.destroy', $car))
            ->assertRedirect(route('cars.index'))
            ->assertSessionHas('success', 'Silvia S15 2001 dihapus dari perbandingan.')
            ->assertSessionHas('compare.cars', []);
    }

    public function test_tambah_dua_kali_tidak_ganda(): void
    {
        $car = $this->car();

        $this->post(route('compare.store', $car));
        $this->post(route('compare.store', $car))
            ->assertSessionHas('status')
            ->assertSessionHas('compare.cars', [$car->id]);
    }

    public function test_maksimal_tiga_mobil(): void
    {
        $cars = Car::factory()->count(CarComparison::MAX_CARS + 1)->create();

        foreach ($cars->take(CarComparison::MAX_CARS) as $car) {
            $this->post(route('compare.store', $car))->assertSessionHas('success');
        }

        $this->post(route('compare.store', $cars->last()))
            ->assertSessionHas('error', 'Maksimal 3 mobil dibandingkan. Hapus salah satu terlebih dahulu.')
            ->assertSessionHas('compare.cars', $cars->take(CarComparison::MAX_CARS)->modelKeys());
    }

    public function test_mobil_nonaktif_tidak_bisa_dipilih(): void
    {
        $this->post(route('compare.store', Car::factory()->inactive()->create()))->assertNotFound();

        $this->assertNull(session('compare.cars'));
    }

    public function test_kosongkan_daftar(): void
    {
        $this->withSession(['compare.cars' => [$this->car()->id, $this->car()->id]])
            ->delete(route('compare.clear'))
            ->assertSessionHas('success', 'Daftar perbandingan dikosongkan.')
            ->assertSessionMissing('compare.cars');
    }

    public function test_mobil_yang_dinonaktifkan_atau_dihapus_dibuang_dari_pilihan(): void
    {
        $active = $this->car(['name' => 'Skyline R34']);
        $inactive = $this->car(['name' => 'Cefiro A31', 'is_active' => false]);

        $this->withSession(['compare.cars' => [$active->id, $inactive->id, 999999]])
            ->get(route('compare.index'))
            ->assertOk()
            ->assertSee('Skyline R34')
            ->assertDontSee('Cefiro A31')
            ->assertSessionHas('compare.cars', [$active->id]);
    }

    // ---------- Halaman bandingkan ----------

    public function test_halaman_kosong_dan_satu_mobil(): void
    {
        $this->get(route('compare.index'))
            ->assertOk()
            ->assertSee('Belum ada mobil yang dibandingkan');

        $this->withSession(['compare.cars' => [$this->car()->id]])
            ->get(route('compare.index'))
            ->assertOk()
            ->assertSee('Pilih minimal satu mobil lagi');
    }

    public function test_tabel_menampilkan_spesifikasi_sesuai_urutan_pilihan_dan_nilai_terbaik(): void
    {
        $old = $this->car([
            'name' => 'Fairlady 240Z', 'year' => 1972, 'vehicle_condition' => Car::CONDITION_USED, 'mileage' => 120_000,
            'price' => 900_000_000, 'engine_cc' => 2400, 'stock' => 1,
        ]);
        $newer = $this->car([
            'name' => 'Skyline R34', 'year' => 1999, 'vehicle_condition' => Car::CONDITION_USED, 'mileage' => 80_000,
            'price' => 1_500_000_000, 'engine_cc' => 2600, 'stock' => 1,
        ]);
        // Promo menurunkan harga akhir di bawah 240Z.
        Promo::factory()->forCar($newer)->create(['discount_amount' => 700_000_000]);

        $html = $this->withSession(['compare.cars' => [$newer->id, $old->id]])
            ->get(route('compare.index'))
            ->assertOk()
            ->assertSeeInOrder(['Skyline R34', 'Fairlady 240Z'])
            ->assertSee('2.600 cc')
            ->assertSee('120.000 km')
            ->getContent();

        // Badge "Terbaik": harga akhir (R34 setelah promo), tahun (R34), km (R34) = 3 badge, semuanya kolom R34.
        $this->assertSame(3, substr_count($html, '>Terbaik</span>'));
        $this->assertMatchesRegularExpression('/Rp\s*800\.000\.000.*?Terbaik/s', $html);
    }

    public function test_tanpa_badge_bila_nilainya_sama(): void
    {
        $a = $this->car(['year' => 2020, 'price' => 300_000_000, 'vehicle_condition' => Car::CONDITION_NEW, 'mileage' => 0]);
        $b = $this->car(['year' => 2020, 'price' => 300_000_000, 'vehicle_condition' => Car::CONDITION_NEW, 'mileage' => 0]);

        $this->withSession(['compare.cars' => [$a->id, $b->id]])
            ->get(route('compare.index'))
            ->assertOk()
            ->assertDontSee('>Terbaik</span>', false);
    }

    public function test_tanpa_n_plus_1(): void
    {
        $count = function (array $ids): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->withSession(['compare.cars' => $ids])->get(route('compare.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $first = $this->car();
        $withOne = $count([$first->id]);
        $withThree = $count([$first->id, $this->car()->id, $this->car()->id]);

        $this->assertSame($withOne, $withThree);
    }

    // ---------- Tombol & bar ----------

    public function test_tombol_bandingkan_di_kartu_dan_detail(): void
    {
        $selected = $this->car();
        $other = $this->car();

        $this->withSession(['compare.cars' => [$selected->id]])
            ->get(route('cars.index'))
            ->assertOk()
            ->assertSee('action="'.route('compare.destroy', $selected).'"', false)
            ->assertSee('action="'.route('compare.store', $other).'"', false);

        $this->get(route('cars.show', $other))->assertSee('Bandingkan');
    }

    public function test_bar_melayang_muncul_hanya_bila_ada_pilihan(): void
    {
        $this->get(route('home'))->assertDontSee('compare-bar', false);

        $this->withSession(['compare.cars' => [$this->car()->id, $this->car()->id]])
            ->get(route('home'))
            ->assertSee('compare-bar', false)
            ->assertSeeInOrder(['<strong>2</strong>', 'dari 3 mobil dipilih'], false);

        // Tidak tampil di halaman bandingkan sendiri.
        $this->withSession(['compare.cars' => [$this->car()->id]])
            ->get(route('compare.index'))
            ->assertDontSee('compare-bar', false);
    }

    public function test_admin_juga_bisa_membandingkan(): void
    {
        $car = $this->car();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('compare.store', $car))
            ->assertSessionHas('compare.cars', [$car->id]);
    }
}
