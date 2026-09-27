<?php

namespace Tests\Feature\Public;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\TestDrive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountTestDriveTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-26 10:00:00', 'Asia/Jakarta'));
        $this->customer = User::factory()->create(['name' => 'Budi Santoso']);
        $this->car = Car::factory()->create([
            'brand_id' => Brand::create(['name' => 'Toyota', 'slug' => 'toyota'])->id,
            'category_id' => Category::create(['name' => 'MPV', 'slug' => 'mpv'])->id,
            'name' => 'Avanza',
            'year' => 2025,
        ]);
    }

    private function makeTestDrive(string $status = TestDrive::STATUS_PENDING, array $attributes = [], ?User $user = null): TestDrive
    {
        return TestDrive::factory()->recycle($user ?? $this->customer)->recycle($this->car)->status($status)->create([
            'preferred_date' => '2026-10-01',
            'preferred_time' => '10:00',
            ...$attributes,
        ]);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $testDrive = $this->makeTestDrive();

        $this->get(route('account.test-drives.index'))->assertRedirect(route('login'));
        $this->patch(route('account.test-drives.cancel', $testDrive))->assertRedirect(route('login'));

        $this->assertSame(TestDrive::STATUS_PENDING, $testDrive->fresh()->status);
    }

    public function test_riwayat_kosong_menampilkan_empty_state(): void
    {
        $this->actingAs($this->customer)->get(route('account.test-drives.index'))
            ->assertOk()
            ->assertSee('Belum ada test drive')
            ->assertSee('href="'.route('test-drives.create').'"', false);
    }

    public function test_riwayat_hanya_milik_sendiri(): void
    {
        $this->makeTestDrive(attributes: ['notes' => 'Catatan milik Budi']);
        $this->makeTestDrive(attributes: ['notes' => 'Catatan orang lain'], user: User::factory()->create());

        $this->actingAs($this->customer)->get(route('account.test-drives.index'))
            ->assertOk()
            ->assertSee('<h1 class="h3 mb-0">Riwayat Test Drive</h1>', false)
            ->assertSee('Toyota Avanza 2025')
            ->assertSee('01 Okt 2026')
            ->assertSee('10:00 WIB')
            ->assertSee('Catatan milik Budi')
            ->assertDontSee('Catatan orang lain');
    }

    public function test_menampilkan_status_dan_catatan_dealer(): void
    {
        $this->makeTestDrive(TestDrive::STATUS_CANCELLED, ['admin_note' => 'Unit sedang servis, silakan booking ulang.']);

        $this->actingAs($this->customer)->get(route('account.test-drives.index'))
            ->assertOk()
            ->assertSee('Dibatalkan')
            ->assertSee('Catatan dealer:')
            ->assertSee('Unit sedang servis, silakan booking ulang.');
    }

    public function test_tombol_batalkan_hanya_untuk_pending(): void
    {
        $pending = $this->makeTestDrive();
        $confirmed = $this->makeTestDrive(TestDrive::STATUS_CONFIRMED, ['preferred_date' => '2026-10-02']);
        $completed = $this->makeTestDrive(TestDrive::STATUS_COMPLETED, ['preferred_date' => '2026-10-03']);
        $cancelled = $this->makeTestDrive(TestDrive::STATUS_CANCELLED, ['preferred_date' => '2026-10-04']);

        $response = $this->actingAs($this->customer)->get(route('account.test-drives.index'))
            ->assertOk()
            ->assertSee(route('account.test-drives.cancel', $pending))
            ->assertSee('data-confirm="Batalkan test drive Toyota Avanza 2025 pada 01 Okt 2026?"', false);

        foreach ([$confirmed, $completed, $cancelled] as $testDrive) {
            $response->assertDontSee(route('account.test-drives.cancel', $testDrive));
        }
    }

    public function test_batal_saat_pending(): void
    {
        $testDrive = $this->makeTestDrive(attributes: ['admin_note' => 'Catatan lama']);

        $this->actingAs($this->customer)
            ->from(route('account.test-drives.index'))
            ->patch(route('account.test-drives.cancel', $testDrive))
            ->assertRedirect(route('account.test-drives.index'))
            ->assertSessionHas('success', 'Test drive berhasil dibatalkan.');

        $testDrive->refresh();
        $this->assertSame(TestDrive::STATUS_CANCELLED, $testDrive->status);
        $this->assertSame('Catatan lama', $testDrive->admin_note);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function notCancellableProvider(): array
    {
        return ['confirmed' => [TestDrive::STATUS_CONFIRMED], 'completed' => [TestDrive::STATUS_COMPLETED]];
    }

    #[DataProvider('notCancellableProvider')]
    public function test_tidak_bisa_batal_selain_pending(string $status): void
    {
        $testDrive = $this->makeTestDrive($status);

        $this->actingAs($this->customer)
            ->from(route('account.test-drives.index'))
            ->patch(route('account.test-drives.cancel', $testDrive))
            ->assertRedirect(route('account.test-drives.index'))
            ->assertSessionHas('error');

        $this->assertSame($status, $testDrive->fresh()->status);
    }

    public function test_klik_ganda_batal_aman(): void
    {
        $testDrive = $this->makeTestDrive();
        $cancel = fn () => $this->actingAs($this->customer)
            ->from(route('account.test-drives.index'))
            ->patch(route('account.test-drives.cancel', $testDrive));

        $cancel()->assertSessionHas('success');
        $cancel()->assertSessionHas('status', 'Test drive ini sudah dibatalkan.');

        $this->assertSame(TestDrive::STATUS_CANCELLED, $testDrive->fresh()->status);
    }

    public function test_tidak_bisa_membatalkan_milik_orang_lain(): void
    {
        $testDrive = $this->makeTestDrive(user: User::factory()->create());

        $this->actingAs($this->customer)
            ->patch(route('account.test-drives.cancel', $testDrive))
            ->assertNotFound();

        $this->assertSame(TestDrive::STATUS_PENDING, $testDrive->fresh()->status);
    }

    public function test_dropdown_navbar_test_drive_saya_hanya_untuk_customer(): void
    {
        $this->actingAs($this->customer)->get(route('home'))
            ->assertOk()
            ->assertSee('Test Drive Saya')
            ->assertSee('href="'.route('account.test-drives.index').'"', false);

        $this->actingAs(User::factory()->admin()->create())->get(route('home'))
            ->assertOk()
            ->assertDontSee('Test Drive Saya');
    }

    public function test_link_mobil_hanya_jika_mobil_aktif(): void
    {
        $this->makeTestDrive();

        $this->actingAs($this->customer)->get(route('account.test-drives.index'))
            ->assertSee('href="'.route('cars.show', $this->car).'"', false);

        $this->car->update(['is_active' => false]);

        $this->actingAs($this->customer)->get(route('account.test-drives.index'))
            ->assertSee('Toyota Avanza 2025')
            ->assertDontSee('href="'.route('cars.show', $this->car).'"', false);
    }

    public function test_jumlah_query_tetap_walau_data_bertambah(): void
    {
        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->customer)->get(route('account.test-drives.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $this->makeTestDrive();
        $queriesWithOne = $count();

        foreach (range(1, 9) as $i) {
            TestDrive::factory()->recycle($this->customer)->create();
        }

        $this->assertSame($queriesWithOne, $count());
    }
}
