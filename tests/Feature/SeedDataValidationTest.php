<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Promo;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\BrandSeeder;
use Database\Seeders\CarSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PromoSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * Regresi Phase 17: setiap data contoh dari seeder harus bisa disimpan ulang lewat form admin
 * tanpa perubahan. Mencegah data seeder yang melanggar validasi (dulu: AE86 tahun 1986 ditolak
 * karena batas tahun 1990), sehingga admin tidak bisa mengubah stok/harga mobil tersebut.
 */
class SeedDataValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([BrandSeeder::class, CategorySeeder::class, CarSeeder::class, PromoSeeder::class, ServiceSeeder::class]);
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * Pesan error validasi dari sesi terakhir; kosong jika lolos.
     *
     * @return list<string>
     */
    private function lastErrors(): array
    {
        $bag = session('errors');
        session()->forget('errors');

        return match (true) {
            is_object($bag) => $bag->all(),
            is_array($bag) => Arr::flatten($bag),
            default => [],
        };
    }

    public function test_semua_mobil_seeder_lolos_validasi_form_admin(): void
    {
        $this->assertGreaterThan(0, Car::count());

        foreach (Car::all() as $car) {
            $this->actingAs($this->admin)->put(route('admin.cars.update', $car), [
                ...$car->only(['brand_id', 'category_id', 'name', 'vehicle_condition', 'year', 'mileage', 'price',
                    'transmission', 'fuel_type', 'engine_cc', 'seats', 'color', 'stock', 'description']),
                'is_active' => $car->is_active ? '1' : null,
            ]);

            $this->assertSame([], $this->lastErrors(), "Mobil seeder \"{$car->name} {$car->year}\" ditolak form admin.");
        }
    }

    public function test_semua_promo_seeder_lolos_validasi_form_admin(): void
    {
        foreach (Promo::all() as $promo) {
            $this->actingAs($this->admin)->put(route('admin.promos.update', $promo), [
                ...$promo->only(['car_id', 'title', 'description', 'discount_amount']),
                'start_date' => $promo->start_date->toDateString(),
                'end_date' => $promo->end_date->toDateString(),
                'is_active' => $promo->is_active ? '1' : null,
            ]);

            $this->assertSame([], $this->lastErrors(), "Promo seeder \"{$promo->title}\" ditolak form admin.");
        }
    }

    public function test_semua_layanan_seeder_lolos_validasi_form_admin(): void
    {
        foreach (Service::all() as $service) {
            $this->actingAs($this->admin)->put(route('admin.services.update', $service), [
                ...$service->only(['name', 'description', 'price_from', 'duration_minutes']),
                'is_active' => $service->is_active ? '1' : null,
            ]);

            $this->assertSame([], $this->lastErrors(), "Layanan seeder \"{$service->name}\" ditolak form admin.");
        }
    }

    public function test_mobil_klasik_ae86_bisa_diubah_stoknya(): void
    {
        $ae86 = Car::where('year', '<', 1990)->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.cars.update', $ae86), [
            ...$ae86->only(['brand_id', 'category_id', 'name', 'vehicle_condition', 'year', 'mileage', 'price',
                'transmission', 'fuel_type', 'engine_cc', 'seats', 'color', 'description']),
            'stock' => 3,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(3, $ae86->fresh()->stock);
    }
}
