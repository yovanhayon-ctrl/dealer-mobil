<?php

namespace Tests\Feature\Public;

use App\Http\Controllers\TestDriveController;
use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\TestDrive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TestDriveBookingTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-26 10:00:00', 'Asia/Jakarta'));
        $this->customer = User::factory()->create(['name' => 'Budi Santoso', 'phone' => '081211112222']);
        $this->car = $this->car('Toyota', 'Avanza');
    }

    private function car(string $brand, string $name, array $attributes = []): Car
    {
        return Car::factory()->create([
            'brand_id' => Brand::firstOrCreate(['name' => $brand], ['slug' => str($brand)->slug()])->id,
            'category_id' => Category::firstOrCreate(['name' => 'MPV'], ['slug' => 'mpv'])->id,
            'name' => $name,
            'slug' => str("{$brand} {$name} 2025")->slug()->value(),
            'year' => 2025,
            'stock' => 2,
            ...$attributes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'car_id' => $this->car->id,
            'preferred_date' => '2026-10-01',
            'preferred_time' => '10:00',
            'phone' => '081234567890',
            'notes' => 'Ingin mencoba di jalan tol.',
            ...$overrides,
        ];
    }

    private function book(array $overrides = [], ?User $user = null): TestResponse
    {
        return $this->actingAs($user ?? $this->customer)
            ->from(route('test-drives.create'))
            ->post(route('test-drives.store'), $this->payload($overrides));
    }

    // ---------- Akses ----------

    public function test_tamu_diarahkan_ke_login_lalu_kembali_ke_form(): void
    {
        $url = route('test-drives.create', ['mobil' => 'toyota-avanza-2025']);

        $this->get($url)->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $this->customer->email, 'password' => 'password'])
            ->assertRedirect($url);
    }

    public function test_tamu_yang_mendaftar_kembali_ke_form(): void
    {
        $url = route('test-drives.create', ['mobil' => 'toyota-avanza-2025']);

        $this->get($url)->assertRedirect(route('login'));

        $this->post(route('register.store'), [
            'name' => 'Sinta',
            'email' => 'sinta@example.test',
            'phone' => '081299998888',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect($url);
    }

    public function test_tamu_tidak_bisa_mengirim_booking(): void
    {
        $this->post(route('test-drives.store'), $this->payload())->assertRedirect(route('login'));

        $this->assertSame(0, TestDrive::count());
    }

    public function test_admin_melihat_pesan_dan_tidak_bisa_booking(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('test-drives.create'))
            ->assertOk()
            ->assertSee('Akun admin tidak dapat booking test drive')
            ->assertDontSee('name="car_id"', false);

        $this->book(user: $admin)
            ->assertRedirect(route('test-drives.create'))
            ->assertSessionHas('error', 'Akun admin tidak dapat booking test drive. Gunakan akun customer.');

        $this->assertSame(0, TestDrive::count());
    }

    // ---------- Form ----------

    public function test_mobil_dari_query_string_terpilih_dan_ringkasan_tampil(): void
    {
        $this->actingAs($this->customer)
            ->get(route('test-drives.create', ['mobil' => 'toyota-avanza-2025']))
            ->assertOk()
            ->assertSee('<option value="'.$this->car->id.'" selected>Toyota Avanza 2025 (Baru)</option>', false)
            ->assertSee('Lihat detail mobil')
            ->assertSee('href="'.route('cars.show', $this->car).'"', false);
    }

    public function test_slug_mobil_tidak_tersedia_menampilkan_peringatan(): void
    {
        $this->car('Honda', 'Jazz', ['vehicle_condition' => Car::CONDITION_USED, 'mileage' => 40_000, 'stock' => 0]);

        $this->actingAs($this->customer)
            ->get(route('test-drives.create', ['mobil' => 'honda-jazz-2025']))
            ->assertOk()
            ->assertSeeInOrder(['Mobil Honda Jazz 2025', 'tidak tersedia untuk test drive.'])
            ->assertDontSee('selected>', false);

        $this->actingAs($this->customer)
            ->get(route('test-drives.create', ['mobil' => 'slug-asal']))
            ->assertOk()
            ->assertDontSee('tidak tersedia untuk test drive')
            ->assertDontSee('selected>', false);
    }

    public function test_dropdown_hanya_mobil_yang_boleh_di_test_drive(): void
    {
        $this->car('Toyota', 'Rush Display', ['stock' => 0]);
        $this->car('Honda', 'Jazz Terjual', ['vehicle_condition' => Car::CONDITION_USED, 'mileage' => 40_000, 'stock' => 0]);
        $this->car('Honda', 'Brio Bekas', ['vehicle_condition' => Car::CONDITION_USED, 'mileage' => 20_000, 'stock' => 1]);
        $this->car('Suzuki', 'Ertiga Nonaktif', ['is_active' => false]);

        $this->actingAs($this->customer)->get(route('test-drives.create'))
            ->assertOk()
            ->assertSeeInOrder(['Honda Brio Bekas 2025 (Bekas)', 'Toyota Avanza 2025 (Baru)', 'Toyota Rush Display 2025 (Baru)'])
            ->assertDontSee('Jazz Terjual')
            ->assertDontSee('Ertiga Nonaktif');
    }

    public function test_nomor_whatsapp_dari_profil_dan_rentang_tanggal(): void
    {
        $this->actingAs($this->customer)->get(route('test-drives.create'))
            ->assertOk()
            ->assertSee('value="081211112222"', false)
            ->assertSee('min="2026-09-27"', false)
            ->assertSee('max="2026-10-26"', false)
            ->assertSeeInOrder(['value="09:00"', '09:00 WIB', 'value="16:00"', '16:00 WIB'], false)
            ->assertSee('data-disable-on-submit', false);
    }

    // ---------- Validasi ----------

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'tanggal hari ini' => [['preferred_date' => '2026-09-26'], 'preferred_date'],
            'tanggal kemarin' => [['preferred_date' => '2026-09-25'], 'preferred_date'],
            'tanggal +31 hari' => [['preferred_date' => '2026-10-27'], 'preferred_date'],
            'format tanggal salah' => [['preferred_date' => '01/10/2026'], 'preferred_date'],
            'tanggal kosong' => [['preferred_date' => ''], 'preferred_date'],
            'jam 08:00' => [['preferred_time' => '08:00'], 'preferred_time'],
            'jam 17:00' => [['preferred_time' => '17:00'], 'preferred_time'],
            'jam 09:30' => [['preferred_time' => '09:30'], 'preferred_time'],
            'jam kosong' => [['preferred_time' => ''], 'preferred_time'],
            'jam array' => [['preferred_time' => ['10:00']], 'preferred_time'],
            'nomor terlalu pendek' => [['phone' => '12345'], 'phone'],
            'nomor bukan 08' => [['phone' => '0712345678'], 'phone'],
            'nomor kosong' => [['phone' => ''], 'phone'],
            'catatan terlalu panjang' => [['notes' => str_repeat('a', 501)], 'notes'],
            'mobil tidak ada' => [['car_id' => 999999], 'car_id'],
            'mobil kosong' => [['car_id' => ''], 'car_id'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function test_validasi_menolak_isian_tidak_valid(array $overrides, string $field): void
    {
        $this->book($overrides)
            ->assertRedirect(route('test-drives.create'))
            ->assertSessionHasErrors($field);

        $this->assertSame(0, TestDrive::count());
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function validBoundaryProvider(): array
    {
        return [
            'besok jam 09:00' => [['preferred_date' => '2026-09-27', 'preferred_time' => '09:00']],
            '+30 hari jam 16:00' => [['preferred_date' => '2026-10-26', 'preferred_time' => '16:00']],
            'catatan kosong' => [['notes' => '']],
            'catatan 500 karakter' => [['notes' => str_repeat('a', 500)]],
        ];
    }

    #[DataProvider('validBoundaryProvider')]
    public function test_batas_rentang_diterima(array $overrides): void
    {
        $this->book($overrides)->assertRedirect(route('account.test-drives.index'))->assertSessionHasNoErrors();

        $this->assertSame(1, TestDrive::count());
    }

    public function test_nomor_dinormalisasi_ke_08(): void
    {
        $this->book(['phone' => '+62 812-3456-7890'])->assertSessionHasNoErrors();

        $this->assertSame('081234567890', TestDrive::first()->phone);
    }

    // ---------- Aturan mobil ----------

    public function test_mobil_nonaktif_ditolak(): void
    {
        $car = $this->car('Suzuki', 'Ertiga', ['is_active' => false]);

        $this->book(['car_id' => $car->id])->assertSessionHasErrors(['car_id' => 'Mobil ini tidak tersedia untuk test drive.']);
        $this->assertSame(0, TestDrive::count());
    }

    public function test_mobil_bekas_stok_0_ditolak(): void
    {
        $car = $this->car('Honda', 'Jazz', ['vehicle_condition' => Car::CONDITION_USED, 'mileage' => 40_000, 'stock' => 0]);

        $this->book(['car_id' => $car->id])->assertSessionHasErrors('car_id');
        $this->assertSame(0, TestDrive::count());
    }

    public function test_mobil_baru_stok_0_dan_bekas_berstok_diterima(): void
    {
        $display = $this->car('Toyota', 'Rush', ['stock' => 0]);
        $used = $this->car('Honda', 'Brio', ['vehicle_condition' => Car::CONDITION_USED, 'mileage' => 20_000, 'stock' => 1]);

        $this->book(['car_id' => $display->id])->assertSessionHasNoErrors();
        $this->book(['car_id' => $used->id])->assertSessionHasNoErrors();

        $this->assertSame(2, TestDrive::count());
    }

    // ---------- Booking ganda & slot ----------

    /**
     * @return array<string, array{0: string}>
     */
    public static function activeStatusProvider(): array
    {
        return ['pending' => [TestDrive::STATUS_PENDING], 'confirmed' => [TestDrive::STATUS_CONFIRMED]];
    }

    #[DataProvider('activeStatusProvider')]
    public function test_booking_ganda_untuk_mobil_yang_sama_ditolak(string $status): void
    {
        TestDrive::factory()->recycle($this->customer)->recycle($this->car)->status($status)->create([
            'preferred_date' => '2026-10-05', 'preferred_time' => '14:00',
        ]);

        $this->book(['preferred_date' => '2026-10-10'])
            ->assertSessionHasErrors(['car_id' => 'Anda sudah punya jadwal test drive untuk mobil ini ('
                .TestDrive::STATUS_LABELS[$status].', 05 Okt 2026 14:00 WIB). Lihat Riwayat Test Drive.']);

        $this->assertSame(1, TestDrive::count());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function finalStatusProvider(): array
    {
        return ['cancelled' => [TestDrive::STATUS_CANCELLED], 'completed' => [TestDrive::STATUS_COMPLETED]];
    }

    #[DataProvider('finalStatusProvider')]
    public function test_boleh_booking_lagi_setelah_status_akhir(string $status): void
    {
        TestDrive::factory()->recycle($this->customer)->recycle($this->car)->status($status)->create();

        $this->book()->assertSessionHasNoErrors();
        $this->assertSame(2, TestDrive::count());
    }

    public function test_mobil_lain_tetap_boleh_saat_punya_booking_aktif(): void
    {
        TestDrive::factory()->recycle($this->customer)->recycle($this->car)->create();
        $other = $this->car('Honda', 'Mobilio');

        $this->book(['car_id' => $other->id])->assertSessionHasNoErrors();
        $this->assertSame(2, TestDrive::count());
    }

    #[DataProvider('activeStatusProvider')]
    public function test_slot_mobil_yang_sudah_dipesan_customer_lain_ditolak(string $status): void
    {
        TestDrive::factory()->recycle($this->car)->status($status)->create([
            'preferred_date' => '2026-10-01', 'preferred_time' => '10:00',
        ]);

        $this->book()
            ->assertSessionHasErrors(['preferred_time' => 'Jadwal ini sudah dipesan, silakan pilih jam atau tanggal lain.'])
            ->assertSessionHasInput('notes', 'Ingin mencoba di jalan tol.');

        $this->assertSame(1, TestDrive::count());
    }

    public function test_slot_lain_mobil_lain_atau_slot_dibatalkan_boleh(): void
    {
        $other = User::factory()->create();
        TestDrive::factory()->recycle($other)->recycle($this->car)->create(['preferred_date' => '2026-10-01', 'preferred_time' => '10:00']);
        TestDrive::factory()->recycle($this->car)->status(TestDrive::STATUS_CANCELLED)
            ->create(['preferred_date' => '2026-10-02', 'preferred_time' => '10:00']);
        $mobilio = $this->car('Honda', 'Mobilio');

        $this->book(['preferred_time' => '11:00'])->assertSessionHasNoErrors();
        $this->book(['car_id' => $mobilio->id, 'preferred_time' => '10:00'], User::factory()->create())->assertSessionHasNoErrors();
        $this->book(['preferred_date' => '2026-10-02', 'preferred_time' => '10:00'], User::factory()->create())->assertSessionHasNoErrors();

        $this->assertSame(5, TestDrive::count());
    }

    // ---------- Rate limit ----------

    public function test_rate_limit_booking_per_user(): void
    {
        foreach (range(1, TestDriveController::MAX_BOOKINGS_PER_MINUTE) as $i) {
            $this->book(['preferred_time' => '08:00'])->assertSessionHasErrors('preferred_time');
        }

        $this->book()
            ->assertRedirect(route('test-drives.create'))
            ->assertSessionHas('error', 'Terlalu banyak percobaan booking. Coba lagi dalam 1 menit.');
        $this->assertSame(0, TestDrive::count());

        // User lain tidak terpengaruh.
        $this->book(user: User::factory()->create())->assertSessionHasNoErrors();

        // Setelah 1 menit boleh lagi.
        $this->travel(61)->seconds();
        $this->book(['preferred_time' => '11:00'])->assertSessionHasNoErrors();
        $this->assertSame(2, TestDrive::count());
    }

    // ---------- Tersimpan ----------

    public function test_booking_tersimpan_pending_dan_diarahkan_ke_riwayat(): void
    {
        $this->book()
            ->assertRedirect(route('account.test-drives.index'))
            ->assertSessionHas('success', 'Booking test drive Toyota Avanza 2025 pada 01 Okt 2026 pukul 10:00 WIB berhasil dikirim. Tunggu konfirmasi dari dealer.');

        $testDrive = TestDrive::sole();
        $this->assertSame($this->customer->id, $testDrive->user_id);
        $this->assertSame($this->car->id, $testDrive->car_id);
        $this->assertSame('2026-10-01', $testDrive->preferred_date->toDateString());
        $this->assertSame('10:00', $testDrive->timeLabel());
        $this->assertSame('081234567890', $testDrive->phone);
        $this->assertSame('Ingin mencoba di jalan tol.', $testDrive->notes);
        $this->assertSame(TestDrive::STATUS_PENDING, $testDrive->status);
        $this->assertNull($testDrive->admin_note);
    }

    public function test_catatan_kosong_disimpan_null(): void
    {
        $this->book(['notes' => '   '])->assertSessionHasNoErrors();

        $this->assertNull(TestDrive::sole()->notes);
    }

    public function test_booking_baru_tampil_di_admin(): void
    {
        $this->book()->assertSessionHasNoErrors();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.test-drives.index'))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Avanza')
            ->assertSee('01 Okt 2026')
            ->assertSee('Menunggu');
    }

    public function test_menu_navbar_test_drive_tampil(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('href="'.route('test-drives.create').'"', false);
    }
}
