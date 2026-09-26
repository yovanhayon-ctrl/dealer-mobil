<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Car;
use App\Models\TestDrive;
use App\Models\User;
use Database\Seeders\CarSeeder;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\TestDriveSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TestDriveManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-09-26 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.test']);
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Toyota'])->id,
            'name' => 'Avanza', 'year' => 2025, 'stock' => 3,
        ]);
    }

    private function makeTestDrive(array $attributes = [], ?Car $car = null): TestDrive
    {
        return TestDrive::factory()->recycle($this->customer)->recycle($car ?? $this->car)->create([
            'preferred_date' => '2026-09-28',
            'preferred_time' => '10:00',
            ...$attributes,
        ]);
    }

    private function changeStatus(TestDrive $testDrive, array $data)
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.test-drives.show', $testDrive))
            ->patch(route('admin.test-drives.update-status', $testDrive), $data);
    }

    /**
     * @return array<int, int>
     */
    private function listedIds(array $query = []): array
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.test-drives.index', $query))
            ->assertOk()
            ->viewData('testDrives')
            ->pluck('id')
            ->sort()
            ->values()
            ->all();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $testDrive = $this->makeTestDrive();

        $this->get('/admin/test-drive')->assertRedirect(route('login'));
        $this->get("/admin/test-drive/{$testDrive->id}")->assertRedirect(route('login'));
        $this->patch("/admin/test-drive/{$testDrive->id}/status", ['status' => 'confirmed'])->assertRedirect(route('login'));

        $this->assertSame('pending', $testDrive->fresh()->status);
    }

    public function test_customer_mendapat_403(): void
    {
        $testDrive = $this->makeTestDrive();

        $this->actingAs($this->customer);
        $this->get('/admin/test-drive')->assertForbidden();
        $this->get("/admin/test-drive/{$testDrive->id}")->assertForbidden();
        $this->patch("/admin/test-drive/{$testDrive->id}/status", ['status' => 'confirmed'])->assertForbidden();

        $this->assertSame('pending', $testDrive->fresh()->status);
    }

    public function test_admin_tidak_bisa_membuat_atau_menghapus(): void
    {
        $testDrive = $this->makeTestDrive();

        $this->actingAs($this->admin);
        $this->post('/admin/test-drive', [])->assertMethodNotAllowed();
        $this->delete("/admin/test-drive/{$testDrive->id}")->assertMethodNotAllowed();
        $this->put("/admin/test-drive/{$testDrive->id}", [])->assertMethodNotAllowed();
        $this->get('/admin/test-drive/tambah')->assertNotFound();

        $this->assertModelExists($testDrive);
    }

    public function test_daftar_menampilkan_data_dan_tanpa_n_plus_1(): void
    {
        $this->makeTestDrive(['status' => 'confirmed']);

        $this->actingAs($this->admin)->get(route('admin.test-drives.index'))
            ->assertOk()
            ->assertSeeInOrder(['28 Sep 2026', '10:00 WIB', 'Budi Santoso', 'budi@example.test', 'Toyota Avanza 2025', 'Dikonfirmasi']);

        $count = function (): int {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->actingAs($this->admin)->get(route('admin.test-drives.index'))->assertOk();

            return count(DB::getQueryLog());
        };

        $queriesWithOne = $count();
        TestDrive::factory()->count(9)->create();
        $this->assertSame($queriesWithOne, $count());
    }

    public function test_filter_status_tanggal_dan_kata_kunci(): void
    {
        $siti = User::factory()->create(['name' => 'Siti Rahmawati', 'email' => 'siti@contoh.test']);
        $honda = Car::factory()->create(['brand_id' => Brand::factory()->create(['name' => 'Honda'])->id, 'name' => 'HR-V']);

        $a = $this->makeTestDrive(['preferred_date' => '2026-09-27', 'status' => 'pending']);
        $b = $this->makeTestDrive(['preferred_date' => '2026-10-05', 'status' => 'confirmed']);
        $c = TestDrive::factory()->recycle($siti)->recycle($honda)->create(['preferred_date' => '2026-10-10', 'status' => 'cancelled']);

        $this->assertSame([$a->id], $this->listedIds(['status' => 'pending']));
        $this->assertSame([$b->id, $c->id], $this->listedIds(['dari' => '2026-10-01']));
        $this->assertSame([$a->id, $b->id], $this->listedIds(['sampai' => '2026-10-05']));
        $this->assertSame([$b->id], $this->listedIds(['dari' => '2026-10-01', 'sampai' => '2026-10-05']));
        $this->assertSame([$c->id], $this->listedIds(['q' => 'siti']));
        $this->assertSame([$c->id], $this->listedIds(['q' => 'contoh.test']));
        $this->assertSame([$c->id], $this->listedIds(['q' => 'hr-v']));
        $this->assertSame([$a->id, $b->id], $this->listedIds(['q' => 'toyota']));
        $this->assertSame([$a->id, $b->id, $c->id], $this->listedIds(['status' => 'ngawur', 'dari' => '31-12-2026']));

        $this->actingAs($this->admin)->get(route('admin.test-drives.index', ['q' => 'tidak-ada']))
            ->assertSee('Test drive tidak ditemukan');
    }

    public function test_urutan_jadwal_terdekat_dan_pagination(): void
    {
        $late = $this->makeTestDrive(['preferred_date' => '2026-10-20']);
        $early = $this->makeTestDrive(['preferred_date' => '2026-09-27']);

        $ids = $this->actingAs($this->admin)->get(route('admin.test-drives.index', ['urut' => 'jadwal']))
            ->viewData('testDrives')->pluck('id')->all();
        $this->assertSame([$early->id, $late->id], $ids);

        TestDrive::factory()->count(10)->create();
        $this->assertCount(10, $this->actingAs($this->admin)->get(route('admin.test-drives.index'))->viewData('testDrives'));
        $this->assertCount(2, $this->actingAs($this->admin)->get(route('admin.test-drives.index', ['page' => 2]))->viewData('testDrives'));
    }

    public function test_detail_menampilkan_data_dan_hanya_status_lanjutan(): void
    {
        $testDrive = $this->makeTestDrive(['notes' => 'Ingin coba di tol.', 'phone' => '081234567801']);

        $this->actingAs($this->admin)->get(route('admin.test-drives.show', $testDrive))
            ->assertOk()
            ->assertSee('Toyota Avanza 2025')
            ->assertSee('Ingin coba di tol.')
            ->assertSee('081234567801')
            ->assertSee('Tetap: Menunggu')
            ->assertSee('<option value="confirmed"', false)
            ->assertSee('<option value="cancelled"', false)
            ->assertDontSee('<option value="completed"', false);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function validTransitionProvider(): array
    {
        return [
            'pending → confirmed' => ['pending', 'confirmed', ''],
            'pending → cancelled' => ['pending', 'cancelled', 'Customer membatalkan jadwal.'],
            'confirmed → completed' => ['confirmed', 'completed', ''],
            'confirmed → cancelled' => ['confirmed', 'cancelled', 'Mobil sedang servis.'],
        ];
    }

    #[DataProvider('validTransitionProvider')]
    public function test_transisi_valid_berhasil(string $from, string $to, string $note): void
    {
        $testDrive = $this->makeTestDrive(['status' => $from, 'preferred_date' => '2026-09-26']);

        $this->changeStatus($testDrive, ['status' => $to, 'admin_note' => $note])
            ->assertRedirect(route('admin.test-drives.show', $testDrive))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Status test drive diubah'));

        $testDrive->refresh();
        $this->assertSame($to, $testDrive->status);
        $this->assertSame($note ?: null, $testDrive->admin_note);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidTransitionProvider(): array
    {
        return [
            'pending → completed' => ['pending', 'completed'],
            'confirmed → pending' => ['confirmed', 'pending'],
            'completed → cancelled' => ['completed', 'cancelled'],
            'cancelled → confirmed' => ['cancelled', 'confirmed'],
            'status tidak dikenal' => ['pending', 'approved'],
        ];
    }

    #[DataProvider('invalidTransitionProvider')]
    public function test_transisi_tidak_valid_ditolak(string $from, string $to): void
    {
        $testDrive = $this->makeTestDrive(['status' => $from, 'preferred_date' => '2026-09-20']);

        $this->changeStatus($testDrive, ['status' => $to, 'admin_note' => 'Catatan percobaan.'])
            ->assertRedirect(route('admin.test-drives.show', $testDrive))
            ->assertSessionHasErrors('status');

        $this->assertSame($from, $testDrive->fresh()->status);
        $this->assertNull($testDrive->fresh()->admin_note);
    }

    /**
     * Simulasikan admin lain yang mengubah status tepat setelah request ini memuat test drive,
     * sehingga validasi memakai salinan lama.
     */
    private function otherAdminChangesStatusFirst(TestDrive $testDrive, string $status): void
    {
        $done = false;

        TestDrive::retrieved(function (TestDrive $retrieved) use ($testDrive, $status, &$done) {
            if (! $done && $retrieved->is($testDrive)) {
                $done = true;
                DB::table('test_drives')->where('id', $testDrive->id)
                    ->update(['status' => $status, 'admin_note' => 'Diputuskan admin lain.']);
            }
        });
    }

    public function test_konfirmasi_ganda_menampilkan_info_tanpa_pesan_diubah(): void
    {
        $testDrive = $this->makeTestDrive();
        $this->otherAdminChangesStatusFirst($testDrive, 'confirmed');

        $this->changeStatus($testDrive, ['status' => 'confirmed', 'admin_note' => 'Catatan admin kedua.'])
            ->assertRedirect(route('admin.test-drives.show', $testDrive))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Test drive ini sudah berstatus Dikonfirmasi. Tidak ada perubahan. Catatan Anda tidak disimpan.')
            ->assertSessionMissing('success');

        $this->assertSame('confirmed', $testDrive->fresh()->status);
        $this->assertSame('Diputuskan admin lain.', $testDrive->fresh()->admin_note, 'Catatan admin pertama tidak boleh tertimpa.');

        $this->actingAs($this->admin)->get(route('admin.test-drives.show', $testDrive))
            ->assertSee('alert-info', false)
            ->assertSee('Test drive ini sudah berstatus Dikonfirmasi. Tidak ada perubahan. Catatan Anda tidak disimpan.')
            ->assertDontSee('diubah dari');
    }

    public function test_status_tetap_yang_dipilih_sengaja_tetap_menyimpan_catatan(): void
    {
        $testDrive = $this->makeTestDrive(['status' => 'confirmed', 'admin_note' => 'Catatan lama.']);

        foreach (['', 'confirmed'] as $keepStatus) {
            $note = "Catatan baru ({$keepStatus}).";

            $this->changeStatus($testDrive, ['status' => $keepStatus, 'admin_note' => $note])
                ->assertSessionHas('success', 'Catatan admin test drive berhasil disimpan.')
                ->assertSessionMissing('status');

            $this->assertSame($note, $testDrive->fresh()->admin_note);
        }

        $this->assertSame('confirmed', $testDrive->fresh()->status);
    }

    public function test_status_yang_sudah_diubah_admin_lain_ke_status_akhir_ditolak(): void
    {
        $testDrive = $this->makeTestDrive();
        $this->otherAdminChangesStatusFirst($testDrive, 'cancelled');

        $this->changeStatus($testDrive, ['status' => 'confirmed'])
            ->assertRedirect(route('admin.test-drives.show', $testDrive))
            ->assertSessionHas('error', 'Status test drive tidak bisa diubah dari Dibatalkan ke Dikonfirmasi.');

        $this->assertSame('cancelled', $testDrive->fresh()->status);
        $this->assertSame('Diputuskan admin lain.', $testDrive->fresh()->admin_note);
    }

    public function test_perubahan_status_nyata_tetap_memakai_pesan_diubah(): void
    {
        $testDrive = $this->makeTestDrive();

        $this->changeStatus($testDrive, ['status' => 'confirmed'])
            ->assertSessionHas('success', 'Status test drive diubah dari Menunggu menjadi Dikonfirmasi.')
            ->assertSessionMissing('status');
    }

    public function test_catatan_wajib_saat_membatalkan_dan_maksimal_1000(): void
    {
        $testDrive = $this->makeTestDrive();

        $this->changeStatus($testDrive, ['status' => 'cancelled', 'admin_note' => ''])
            ->assertSessionHasErrors(['admin_note' => 'Catatan admin wajib diisi saat membatalkan test drive (alasan untuk customer).']);
        $this->changeStatus($testDrive, ['status' => 'cancelled', 'admin_note' => 'abc'])
            ->assertSessionHasErrors('admin_note');
        $this->changeStatus($testDrive, ['status' => 'confirmed', 'admin_note' => str_repeat('a', 1001)])
            ->assertSessionHasErrors('admin_note');

        $this->assertSame('pending', $testDrive->fresh()->status);
    }

    public function test_hanya_mengubah_catatan_dengan_status_tetap(): void
    {
        $testDrive = $this->makeTestDrive(['status' => 'completed', 'preferred_date' => '2026-09-20']);

        $this->changeStatus($testDrive, ['status' => '', 'admin_note' => 'Customer puas, lanjut pengajuan.'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Catatan admin test drive berhasil disimpan.');

        $this->assertSame('completed', $testDrive->fresh()->status);
        $this->assertSame('Customer puas, lanjut pengajuan.', $testDrive->fresh()->admin_note);

        $this->actingAs($this->admin)->get(route('admin.test-drives.show', $testDrive))
            ->assertSee('status akhir');
    }

    public function test_catatan_tidak_boleh_dikosongkan_pada_test_drive_yang_dibatalkan(): void
    {
        $testDrive = $this->makeTestDrive(['status' => 'cancelled', 'admin_note' => 'Alasan awal.']);

        $this->changeStatus($testDrive, ['status' => '', 'admin_note' => ''])->assertSessionHasErrors('admin_note');

        $this->assertSame('Alasan awal.', $testDrive->fresh()->admin_note);
    }

    public function test_mobil_bekas_stok_0_tidak_bisa_dikonfirmasi_tetapi_bisa_dibatalkan(): void
    {
        $soldUsedCar = Car::factory()->used()->create(['stock' => 0]);
        $testDrive = $this->makeTestDrive([], $soldUsedCar);

        $this->actingAs($this->admin)->get(route('admin.test-drives.show', $testDrive))
            ->assertSee('Unit mobil bekas ini sudah terjual');

        $this->changeStatus($testDrive, ['status' => 'confirmed'])
            ->assertSessionHasErrors(['status' => 'Unit mobil bekas ini sudah terjual (stok 0), test drive tidak bisa dikonfirmasi. Batalkan dengan catatan untuk customer.']);
        $this->assertSame('pending', $testDrive->fresh()->status);

        $this->changeStatus($testDrive, ['status' => 'cancelled', 'admin_note' => 'Unit sudah terjual.'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $testDrive->fresh()->status);
    }

    public function test_mobil_baru_stok_0_tetap_bisa_dikonfirmasi(): void
    {
        $displayUnit = Car::factory()->outOfStock()->create();
        $testDrive = $this->makeTestDrive([], $displayUnit);

        $this->changeStatus($testDrive, ['status' => 'confirmed'])->assertSessionHasNoErrors();

        $this->assertSame('confirmed', $testDrive->fresh()->status);
    }

    public function test_selesai_hanya_jika_tanggal_jadwal_sudah_tiba(): void
    {
        $future = $this->makeTestDrive(['status' => 'confirmed', 'preferred_date' => '2026-09-27']);
        $today = $this->makeTestDrive(['status' => 'confirmed', 'preferred_date' => '2026-09-26']);

        $this->changeStatus($future, ['status' => 'completed'])
            ->assertSessionHasErrors(['status' => 'Test drive belum bisa ditandai selesai sebelum tanggal jadwalnya (27 Sep 2026).']);
        $this->assertSame('confirmed', $future->fresh()->status);

        $this->changeStatus($today, ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $today->fresh()->status);
    }

    public function test_riwayat_di_detail_pengguna_kini_tertaut(): void
    {
        $testDrive = $this->makeTestDrive();

        $this->actingAs($this->admin)->get(route('admin.users.show', $this->customer))
            ->assertOk()
            ->assertSee('href="'.route('admin.test-drives.show', $testDrive).'"', false);
    }

    public function test_seeder_test_drive_aman_dijalankan_ulang_dengan_variasi_status(): void
    {
        config(['dealer.seed.customer_password' => 'rahasia-lokal']);
        $this->seed([CarSeeder::class, CustomerSeeder::class]);

        $this->seed(TestDriveSeeder::class);
        $this->seed(TestDriveSeeder::class);

        $seeded = TestDrive::with('car:id,slug,vehicle_condition,stock')->get();
        $this->assertCount(10, $seeded);
        $this->assertEqualsCanonicalizing(TestDrive::STATUSES, $seeded->pluck('status')->unique()->values()->all());
        $this->assertTrue($seeded->every(fn (TestDrive $testDrive) => str_ends_with($testDrive->user()->value('email'), '@example.test')));

        // Tidak ada test drive terkonfirmasi untuk mobil bekas yang sudah terjual.
        $this->assertFalse($seeded->contains(fn (TestDrive $testDrive) => $testDrive->status === 'confirmed'
            && ! $testDrive->car->isNew() && ! $testDrive->car->inStock()));
        // Test drive selesai selalu di masa lalu/hari ini.
        $this->assertTrue($seeded->where('status', 'completed')->every(fn (TestDrive $testDrive) => ! $testDrive->preferred_date->isFuture()));
    }

    public function test_seeder_test_drive_dilewati_tanpa_customer_dummy(): void
    {
        $this->seed(CarSeeder::class);
        $this->seed(TestDriveSeeder::class);

        $this->assertSame(0, TestDrive::count());
    }

    public function test_seeder_test_drive_tidak_berjalan_di_production(): void
    {
        config(['dealer.seed.customer_password' => 'rahasia-lokal']);
        $this->seed([CarSeeder::class, CustomerSeeder::class]);
        $this->app['env'] = 'production';

        // Dipanggil langsung: perintah db:seed di production meminta konfirmasi interaktif.
        $this->app->make(TestDriveSeeder::class)->run();

        $this->assertSame(0, TestDrive::count());
    }
}
