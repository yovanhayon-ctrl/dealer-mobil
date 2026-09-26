<?php

namespace Tests\Feature\Admin;

use App\Models\Brand;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\TestDrive;
use App\Models\User;
use Database\Seeders\CustomerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Administrator', 'email' => 'admin@example.test']);
    }

    /**
     * Nama pengguna di halaman daftar (sesuai filter & urutan), diambil dari data view.
     *
     * @return array<int, string>
     */
    private function listedNames(array $query = []): array
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.users.index', $query))
            ->assertOk()
            ->viewData('users')
            ->pluck('name')
            ->all();
    }

    private function countQueries(string $url): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->actingAs($this->admin)->get($url)->assertOk();

        return count(DB::getQueryLog());
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $customer = User::factory()->create();

        $this->get('/admin/pengguna')->assertRedirect(route('login'));
        $this->get("/admin/pengguna/{$customer->id}")->assertRedirect(route('login'));
    }

    public function test_customer_mendapat_403(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer);
        $this->get('/admin/pengguna')->assertForbidden();
        $this->get("/admin/pengguna/{$customer->id}")->assertForbidden();
    }

    public function test_tidak_ada_route_tambah_ubah_hapus(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($this->admin);
        $this->post('/admin/pengguna', ['name' => 'X'])->assertMethodNotAllowed();
        $this->put("/admin/pengguna/{$customer->id}", ['name' => 'X'])->assertMethodNotAllowed();
        $this->delete("/admin/pengguna/{$customer->id}")->assertMethodNotAllowed();
        $this->get('/admin/pengguna/tambah')->assertNotFound();
        $this->get("/admin/pengguna/{$customer->id}/ubah")->assertNotFound();

        $this->assertModelExists($customer);
        $this->assertNotSame('X', $customer->fresh()->name);
    }

    public function test_daftar_default_hanya_customer_dan_filter_role(): void
    {
        User::factory()->create(['name' => 'Budi Santoso']);
        User::factory()->admin()->create(['name' => 'Admin Kedua']);

        $this->assertSame(['Budi Santoso'], $this->listedNames());
        $this->assertSame(['Budi Santoso'], $this->listedNames(['role' => 'customer']));
        $this->assertSame(['Budi Santoso'], $this->listedNames(['role' => 'ngawur']));
        $this->assertEqualsCanonicalizing(['Administrator', 'Admin Kedua'], $this->listedNames(['role' => 'admin']));
        $this->assertEqualsCanonicalizing(['Administrator', 'Admin Kedua', 'Budi Santoso'], $this->listedNames(['role' => 'semua']));
    }

    public function test_daftar_menampilkan_kolom_dan_jumlah_riwayat(): void
    {
        $this->travelTo(Carbon::parse('2026-09-26 10:00:00', 'Asia/Jakarta'));
        $budi = User::factory()->create([
            'name' => 'Budi Santoso', 'email' => 'budi@example.test', 'phone' => '081234567801',
        ]);
        User::factory()->create(['name' => 'Tanpa HP', 'phone' => null]);
        TestDrive::factory()->count(2)->recycle($budi)->create();
        PurchaseRequest::factory()->count(3)->recycle($budi)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeInOrder(['Budi Santoso', 'budi@example.test', '081234567801', 'Customer', '2', '3', '26 Sep 2026'])
            ->assertSee('href="'.route('admin.users.show', $budi).'"', false)
            ->assertSee('—');

        $listed = $response->viewData('users')->firstWhere('id', $budi->id);
        $this->assertSame(2, $listed->test_drives_count);
        $this->assertSame(3, $listed->purchase_requests_count);
    }

    public function test_pencarian_nama_email_dan_nomor_hp(): void
    {
        User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.test', 'phone' => '081111111111']);
        User::factory()->create(['name' => 'Siti Rahmawati', 'email' => 'siti@contoh.test', 'phone' => '082222222222']);

        $this->assertSame(['Budi Santoso'], $this->listedNames(['q' => 'santoso']));
        $this->assertSame(['Siti Rahmawati'], $this->listedNames(['q' => 'contoh.test']));
        $this->assertSame(['Siti Rahmawati'], $this->listedNames(['q' => '08222']));
        $this->assertSame([], $this->listedNames(['q' => 'administrator']));
        $this->assertSame(['Administrator'], $this->listedNames(['q' => 'administrator', 'role' => 'admin']));

        $this->actingAs($this->admin)->get(route('admin.users.index', ['q' => 'tidak-ada']))
            ->assertSee('Pengguna tidak ditemukan');
    }

    public function test_urutan_terbaru_dan_nama(): void
    {
        User::factory()->create(['name' => 'Citra', 'created_at' => now()->subDays(3)]);
        User::factory()->create(['name' => 'Andi', 'created_at' => now()->subDays(1)]);
        User::factory()->create(['name' => 'Bayu', 'created_at' => now()->subDays(2)]);

        $this->assertSame(['Andi', 'Bayu', 'Citra'], $this->listedNames());
        $this->assertSame(['Andi', 'Bayu', 'Citra'], $this->listedNames(['urut' => 'nama']));

        User::factory()->create(['name' => 'Zaki', 'created_at' => now()]);
        $this->assertSame(['Zaki', 'Andi', 'Bayu', 'Citra'], $this->listedNames(['urut' => 'terbaru']));
        $this->assertSame(['Andi', 'Bayu', 'Citra', 'Zaki'], $this->listedNames(['urut' => 'nama']));
    }

    public function test_pagination_sepuluh_per_halaman(): void
    {
        User::factory()->count(12)->create();

        $this->assertCount(10, $this->listedNames());
        $this->assertCount(2, $this->listedNames(['page' => 2]));
    }

    public function test_empty_state_jika_belum_ada_customer(): void
    {
        $this->actingAs($this->admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Belum ada pengguna');
    }

    public function test_detail_menampilkan_profil_dan_riwayat(): void
    {
        $budi = User::factory()->create([
            'name' => 'Budi Santoso', 'email' => 'budi@example.test', 'phone' => '081234567801',
            'created_at' => Carbon::parse('2026-09-01 08:00:00'),
        ]);
        $car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Toyota'])->id,
            'name' => 'Avanza', 'year' => 2025,
        ]);
        TestDrive::factory()->recycle($budi)->recycle($car)->status('confirmed')->create([
            'preferred_date' => '2026-10-05', 'preferred_time' => '10:00',
        ]);
        PurchaseRequest::factory()->credit()->recycle($budi)->recycle($car)->status('processing')->create([
            'car_price' => 270_000_000,
        ]);

        $other = User::factory()->create(['name' => 'Customer Lain']);
        TestDrive::factory()->recycle($other)->create(['preferred_date' => '2026-12-31']);

        $this->actingAs($this->admin)->get(route('admin.users.show', $budi))
            ->assertOk()
            ->assertSeeInOrder(['Budi Santoso', 'Customer', 'budi@example.test', '081234567801', '01 September 2026'])
            ->assertSeeInOrder(['Riwayat Test Drive', 'Toyota Avanza 2025', '05 Okt 2026', '10:00 WIB', 'Dikonfirmasi'])
            ->assertSeeInOrder(['Riwayat Pengajuan', 'Toyota Avanza 2025', 'Kredit', 'Rp 270.000.000', 'Diproses'])
            ->assertDontSee('31 Des 2026')
            ->assertDontSee('Belum ada test drive')
            ->assertDontSee('Belum ada pengajuan');
    }

    public function test_detail_tanpa_riwayat_menampilkan_empty_state(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.users.show', $customer))
            ->assertOk()
            ->assertSee('Belum ada test drive')
            ->assertSee('Belum ada pengajuan');
    }

    public function test_detail_pengguna_tidak_dikenal_404(): void
    {
        $this->actingAs($this->admin)->get('/admin/pengguna/999999')->assertNotFound();
    }

    public function test_password_dan_remember_token_tidak_pernah_tampil(): void
    {
        $customer = User::factory()->create(['remember_token' => 'token-rahasia-123']);
        $hash = $customer->getAttributes()['password'];

        foreach ([route('admin.users.index'), route('admin.users.show', $customer)] as $url) {
            $this->actingAs($this->admin)->get($url)
                ->assertOk()
                ->assertDontSee($hash, false)
                ->assertDontSee('token-rahasia-123', false);
        }

        $listed = $this->actingAs($this->admin)->get(route('admin.users.index'))->viewData('users')->first();
        $this->assertArrayNotHasKey('password', $listed->getAttributes());
        $this->assertArrayNotHasKey('remember_token', $listed->getAttributes());
    }

    public function test_jumlah_query_daftar_tidak_bertambah_seiring_data(): void
    {
        $customer = User::factory()->create();
        TestDrive::factory()->recycle($customer)->create();
        $queriesWithOne = $this->countQueries(route('admin.users.index'));

        foreach (User::factory()->count(9)->create() as $user) {
            TestDrive::factory()->recycle($user)->create();
            PurchaseRequest::factory()->recycle($user)->create();
        }

        $this->assertSame($queriesWithOne, $this->countQueries(route('admin.users.index')));
    }

    public function test_jumlah_query_detail_tidak_bertambah_seiring_riwayat(): void
    {
        $customer = User::factory()->create();
        TestDrive::factory()->recycle($customer)->create();
        PurchaseRequest::factory()->recycle($customer)->create();
        $queriesWithOne = $this->countQueries(route('admin.users.show', $customer));

        // Mobil & merek berbeda di setiap riwayat agar N+1 pasti terlihat.
        TestDrive::factory()->count(5)->recycle($customer)->create();
        PurchaseRequest::factory()->count(5)->recycle($customer)->create();

        $this->assertSame($queriesWithOne, $this->countQueries(route('admin.users.show', $customer)));
    }

    public function test_seeder_customer_membuat_delapan_customer_dan_aman_dijalankan_ulang(): void
    {
        config(['dealer.seed.customer_password' => 'rahasia-lokal']);

        $this->seed(CustomerSeeder::class);

        $customers = User::where('email', 'like', '%@example.test')->where('role', User::ROLE_CUSTOMER)->get();
        $this->assertCount(8, $customers);
        $this->assertTrue($customers->every(fn (User $user) => filled($user->phone) && $user->email_verified_at !== null));
        $this->assertTrue(Hash::check('rahasia-lokal', $customers->first()->password));

        config(['dealer.seed.customer_password' => 'kata-sandi-baru']);
        $this->seed(CustomerSeeder::class);

        $this->assertSame(8, User::where('role', User::ROLE_CUSTOMER)->count());
        $this->assertTrue(Hash::check('rahasia-lokal', User::firstWhere('email', 'budi.santoso@example.test')->password));
    }

    public function test_seeder_customer_tidak_mengubah_user_yang_sudah_ada(): void
    {
        config(['dealer.seed.customer_password' => 'rahasia-lokal']);
        $existing = User::factory()->admin()->create(['email' => 'budi.santoso@example.test', 'name' => 'Budi Admin']);

        $this->seed(CustomerSeeder::class);

        $existing->refresh();
        $this->assertTrue($existing->isAdmin());
        $this->assertSame('Budi Admin', $existing->name);
        $this->assertFalse(Hash::check('rahasia-lokal', $existing->password));
    }

    public function test_seeder_customer_dilewati_jika_kata_sandi_kosong(): void
    {
        config(['dealer.seed.customer_password' => null]);

        $this->seed(CustomerSeeder::class);

        $this->assertSame(0, User::where('role', User::ROLE_CUSTOMER)->count());
    }

    public function test_seeder_customer_tidak_berjalan_di_production(): void
    {
        config(['dealer.seed.customer_password' => 'rahasia-lokal']);
        $this->app['env'] = 'production';

        // Dipanggil langsung: perintah db:seed di production meminta konfirmasi interaktif.
        $this->app->make(CustomerSeeder::class)->run();

        $this->assertSame(0, User::where('role', User::ROLE_CUSTOMER)->count());
    }
}
