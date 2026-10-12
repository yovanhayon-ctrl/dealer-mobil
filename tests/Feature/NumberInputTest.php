<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\Service;
use App\Models\User;
use App\Support\NumberInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Isian angka (harga, kilometer, DP, tahun, durasi, nomor WhatsApp) di halaman publik & admin:
 * huruf ditolak server dengan pesan jelas, titik ribuan/awalan "Rp" tetap diterima, dan form memakai number-input.js.
 */
class NumberInputTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create(['phone' => '081211112222']);
    }

    public function test_format_angka_yang_diterima_dan_ditolak(): void
    {
        foreach (['285000000', '285.000.000', 'Rp 285.000.000', 'rp285.000.000', 'Rp. 45 000', ' 45.000 '] as $value) {
            $this->assertTrue(NumberInput::isValid($value), $value);
        }

        foreach (['45rb', 'abc', '1,2 M', '60jt', '-5', '12e3', 'Rp', '.500'] as $value) {
            $this->assertFalse(NumberInput::isValid($value), $value);
        }

        $this->assertSame('285000000', NumberInput::normalize('Rp 285.000.000'));
        $this->assertSame('45rb', NumberInput::normalize('45rb'));
        $this->assertNull(NumberInput::normalize('  '));
        $this->assertNull(NumberInput::normalize(['1']));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidNumbers(): array
    {
        return [
            'singkatan ribu' => ['45rb', 'hanya boleh berisi angka'],
            'huruf' => ['abc', 'hanya boleh berisi angka'],
            'koma desimal' => ['1,2 M', 'hanya boleh berisi angka'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_admin_mobil_menolak_harga_dan_kilometer_berhuruf(string $value, string $message): void
    {
        foreach (['price', 'mileage', 'year', 'stock'] as $field) {
            $this->assertRejected($this->postCar([$field => $value]), $field, $message);
        }
    }

    public function test_admin_mobil_menerima_titik_ribuan_dan_awalan_rp(): void
    {
        $this->postCar(['price' => 'Rp 285.000.000', 'mileage' => '45.000'])->assertSessionHasNoErrors();

        $car = Car::query()->sole();
        $this->assertSame(285_000_000, $car->price);
        $this->assertSame(45_000, $car->mileage);
    }

    #[DataProvider('invalidNumbers')]
    public function test_admin_promo_dan_layanan_menolak_angka_berhuruf(string $value, string $message): void
    {
        $car = Car::factory()->create(['price' => 285_000_000]);

        $this->assertRejected($this->actingAs($this->admin)->post(route('admin.promos.store'), [
            'car_id' => $car->id, 'title' => 'Diskon', 'discount_amount' => $value,
            'start_date' => '2026-10-01', 'end_date' => '2026-10-31', 'is_active' => '1',
        ]), 'discount_amount', $message);

        foreach (['price_from', 'duration_minutes'] as $field) {
            $this->assertRejected($this->actingAs($this->admin)->post(route('admin.services.store'), [
                'name' => 'Servis Berkala', 'price_from' => '450.000', 'duration_minutes' => '120', 'is_active' => '1',
                $field => $value,
            ]), $field, $message);
        }
    }

    #[DataProvider('invalidNumbers')]
    public function test_booking_servis_menolak_tahun_dan_kilometer_berhuruf(string $value, string $message): void
    {
        foreach (['vehicle_year', 'mileage'] as $field) {
            $this->assertRejected($this->bookService([$field => $value]), $field, $message);
        }
    }

    public function test_booking_servis_menerima_kilometer_dengan_titik(): void
    {
        $this->bookService(['mileage' => '45.000'])->assertSessionHasNoErrors();
    }

    #[DataProvider('invalidNumbers')]
    public function test_pengajuan_pembelian_menolak_dp_berhuruf(string $value, string $message): void
    {
        $this->assertRejected($this->postPurchase(['down_payment' => $value]), 'down_payment', $message);
    }

    public function test_nomor_whatsapp_berhuruf_ditolak_di_semua_form(): void
    {
        $phone = '0812abc45678';
        $message = 'tidak valid';

        $this->assertRejected($this->postPurchase(['phone' => $phone]), 'phone', $message);
        $this->assertRejected($this->bookService(['phone' => $phone]), 'phone', $message);
        $this->assertRejected($this->actingAs($this->customer)->post(route('test-drives.store'), [
            'car_id' => Car::factory()->create()->id, 'preferred_date' => '2026-10-12', 'preferred_time' => '10:00', 'phone' => $phone,
        ]), 'phone', $message);
        $this->assertRejected($this->actingAs($this->customer)->patch(route('account.profile.update'), [
            'name' => 'Budi', 'email' => $this->customer->email, 'phone' => $phone,
        ]), 'phone', $message);

        auth()->logout();
        $this->assertRejected($this->post(route('register.store'), [
            'name' => 'Budi', 'email' => 'budi@example.com', 'phone' => $phone,
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ]), 'phone', $message);
    }

    public function test_simulasi_kredit_menolak_harga_dan_dp_berhuruf(): void
    {
        $this->get(route('credit.index', ['harga' => '300jt']))
            ->assertOk()
            ->assertViewHas('result', null)
            ->assertSee('Harga hanya boleh berisi angka.');

        $this->get(route('credit.index', ['harga' => '300.000.000', 'dp' => '60jt']))
            ->assertOk()
            ->assertViewHas('result', null)
            ->assertSee('Uang muka hanya boleh berisi angka.');

        $this->get(route('credit.index', ['harga' => 'Rp 300.000.000', 'dp' => 'Rp 60.000.000']))
            ->assertOk()
            ->assertViewHas('result', fn ($result) => $result !== null);
    }

    public function test_form_memakai_skrip_isian_angka(): void
    {
        $car = Car::factory()->create(['is_active' => true, 'stock' => 3]);
        Service::factory()->create(['is_active' => true]);

        $catalog = $this->get(route('cars.index'))->assertSee('js/number-input.js', false);
        $this->assertNumberField($catalog, 'harga_min', 'thousands');
        $this->assertNumberField($catalog, 'tahun_min', 'digits');
        $this->assertNumberField($this->get(route('credit.index')), 'dp', 'thousands');
        $this->assertNumberField($this->get(route('register')), 'phone', 'phone');

        $this->actingAs($this->customer);
        $purchase = $this->get(route('purchase-requests.create', $car));
        $this->assertNumberField($purchase, 'down_payment', 'thousands');
        $this->assertNumberField($purchase, 'phone', 'phone');
        $booking = $this->get(route('service-bookings.create'));
        $this->assertNumberField($booking, 'mileage', 'thousands');
        $this->assertNumberField($booking, 'vehicle_year', 'digits');
        $this->assertNumberField($this->get(route('test-drives.create', ['car' => $car->slug])), 'phone', 'phone');
        $this->assertNumberField($this->get(route('account.profile')), 'phone', 'phone');

        $this->actingAs($this->admin);
        $carForm = $this->get(route('admin.cars.create'))->assertSee('js/number-input.js', false);
        $this->assertNumberField($carForm, 'price', 'thousands');
        $this->assertNumberField($carForm, 'mileage', 'thousands');
        $this->assertNumberField($carForm, 'stock', 'digits');
        $carForm->assertSee('max="9999"', false);
        $this->assertNumberField($this->get(route('admin.promos.create')), 'discount_amount', 'thousands');
        $this->assertNumberField($this->get(route('admin.services.create')), 'duration_minutes', 'digits');
    }

    /** Tag <input name="..."> memuat data-number yang benar (urutan atribut bebas). */
    private function assertNumberField(TestResponse $response, string $name, string $type): void
    {
        preg_match_all('/<input[^>]*>/', (string) $response->assertOk()->getContent(), $inputs);

        $field = collect($inputs[0])->first(fn (string $input) => str_contains($input, 'name="'.$name.'"'));

        $this->assertNotNull($field, "Isian {$name} tidak ditemukan.");
        $this->assertStringContainsString('data-number="'.$type.'"', $field, $name);
    }

    private function assertRejected(TestResponse $response, string $field, string $message): void
    {
        $response->assertSessionHasErrors($field);
        $this->assertStringContainsString($message, session('errors')->first($field), $field);
    }

    private function postCar(array $overrides): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.cars.store'), [
            'brand_id' => Brand::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'name' => 'Avanza', 'vehicle_condition' => 'bekas', 'year' => 2022, 'mileage' => '30.000',
            'price' => '285.000.000', 'transmission' => 'automatic', 'fuel_type' => 'bensin',
            'engine_cc' => 1496, 'seats' => 7, 'color' => 'Putih', 'stock' => 5, 'is_active' => '1',
            ...$overrides,
        ]);
    }

    private function bookService(array $overrides): TestResponse
    {
        return $this->actingAs($this->customer)->post(route('service-bookings.store'), [
            'service_id' => Service::factory()->create()->id,
            'vehicle_model' => 'Nissan Livina VL', 'plate_number' => 'B 1234 ABC', 'vehicle_year' => '2020',
            'mileage' => '45.000', 'preferred_date' => '2026-10-12', 'preferred_time' => '09:00', 'phone' => '081234567890',
            ...$overrides,
        ]);
    }

    private function postPurchase(array $overrides): TestResponse
    {
        $car = Car::factory()->create(['price' => 520_000_000, 'stock' => 3, 'is_active' => true]);

        return $this->actingAs($this->customer)->post(route('purchase-requests.store', $car), [
            'payment_method' => 'credit', 'down_payment' => '104.000.000', 'tenor_months' => '36',
            'phone' => '081234567890', 'address' => 'Jl. Merdeka No. 10, Bandung',
            ...$overrides,
        ]);
    }
}
