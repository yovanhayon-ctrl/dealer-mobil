<?php

namespace Tests\Feature\Admin;

use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\TestDrive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_customer_mendapat_403(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_melihat_angka_statistik_yang_benar(): void
    {
        $admin = User::factory()->admin()->create();
        $customers = User::factory()->count(4)->create();

        Car::factory()->count(3)->create(['stock' => 5]);
        Car::factory()->count(2)->outOfStock()->create();
        Car::factory()->inactive()->outOfStock()->create();
        $cars = Car::all();

        TestDrive::factory()->count(2)->recycle($customers)->recycle($cars)->create();
        TestDrive::factory()->status('confirmed')->recycle($customers)->recycle($cars)->create();

        PurchaseRequest::factory()->count(3)->recycle($customers)->recycle($cars)->create();
        PurchaseRequest::factory()->status('approved')->recycle($customers)->recycle($cars)->create();

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertViewHas('stats', [
                'active_cars' => 5,
                'out_of_stock_cars' => 2,
                'pending_test_drives' => 2,
                'pending_purchases' => 3,
                'customers' => 4,
            ])
            ->assertSeeInOrder(['Mobil Aktif', 'Stok Habis', 'Test Drive Pending', 'Pengajuan Pending', 'Customer']);
    }

    public function test_tabel_hanya_menampilkan_lima_data_yang_relevan(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();
        $car = Car::factory()->create();

        $purchases = collect(range(1, 7))->map(fn (int $daysAgo) => PurchaseRequest::factory()
            ->recycle([$customer, $car])
            ->create(['created_at' => now()->subDays($daysAgo)]));

        $yesterday = TestDrive::factory()->recycle([$customer, $car])
            ->create(['preferred_date' => today()->subDay()]);
        $cancelled = TestDrive::factory()->status('cancelled')->recycle([$customer, $car])
            ->create(['preferred_date' => today()]);
        $upcoming = collect(range(6, 1))->map(fn (int $days) => TestDrive::factory()
            ->status($days % 2 ? 'pending' : 'confirmed')
            ->recycle([$customer, $car])
            ->create(['preferred_date' => today()->addDays($days), 'preferred_time' => '10:00']));

        $response = $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        $response->assertViewHas('latestPurchases', fn ($items) => $items->pluck('id')->all()
            === $purchases->take(5)->pluck('id')->all());

        $response->assertViewHas('upcomingTestDrives', function ($items) use ($upcoming, $yesterday, $cancelled) {
            $expected = $upcoming->sortBy('preferred_date')->take(5)->pluck('id')->values()->all();

            return $items->pluck('id')->all() === $expected
                && ! $items->contains('id', $yesterday->id)
                && ! $items->contains('id', $cancelled->id);
        });
    }

    public function test_empty_state_tampil_saat_data_kosong(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Belum ada pengajuan')
            ->assertSee('Belum ada jadwal test drive');
    }

    public function test_jumlah_query_tidak_bertambah_seiring_data(): void
    {
        $admin = User::factory()->admin()->create();

        $this->seedDashboardData(1);
        $queriesWithOneRow = $this->countDashboardQueries($admin);

        $this->seedDashboardData(4);
        $queriesWithFiveRows = $this->countDashboardQueries($admin);

        $this->assertSame($queriesWithOneRow, $queriesWithFiveRows);
    }

    public function test_sidebar_hanya_menampilkan_menu_yang_route_nya_ada(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('class="nav-link active" href="'.route('admin.dashboard').'"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('href="'.route('admin.cars.index').'"', false)
            ->assertDontSee('href="'.url('/admin/promo').'"', false)
            ->assertDontSee('Laporan');
    }

    private function seedDashboardData(int $count): void
    {
        // Tiap baris memakai customer & mobil (dengan merek) berbeda agar N+1 pasti terlihat.
        TestDrive::factory()->count($count)->create();
        PurchaseRequest::factory()->count($count)->create();
    }

    private function countDashboardQueries(User $admin): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
