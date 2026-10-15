<?php

namespace Tests\Feature;

use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Jadwal test drive / servis yang sudah lewat tetapi masih Menunggu atau Dikonfirmasi.
 */
class ScheduleOverdueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create();
    }

    /**
     * @return array<string, array{class-string, string, string}>
     */
    public static function schedules(): array
    {
        return [
            'test drive' => [TestDrive::class, 'admin.test-drives.index', 'account.test-drives.index'],
            'booking servis' => [ServiceBooking::class, 'admin.service-bookings.index', 'account.service-bookings.index'],
        ];
    }

    /**
     * @param  class-string<TestDrive|ServiceBooking>  $model
     */
    private function schedule(string $model, string $status, string $date): TestDrive|ServiceBooking
    {
        return $model::factory()->recycle($this->customer)->status($status)->create(['preferred_date' => $date]);
    }

    /**
     * @param  class-string<TestDrive|ServiceBooking>  $model
     */
    #[DataProvider('schedules')]
    public function test_hanya_menunggu_atau_dikonfirmasi_sebelum_hari_ini(string $model): void
    {
        $pendingYesterday = $this->schedule($model, 'pending', '2026-10-14');
        $confirmedLastWeek = $this->schedule($model, 'confirmed', '2026-10-08');
        $today = $this->schedule($model, 'pending', '2026-10-15');
        $completed = $this->schedule($model, 'completed', '2026-10-10');
        $cancelled = $this->schedule($model, 'cancelled', '2026-10-10');

        $this->assertEqualsCanonicalizing([$pendingYesterday->id, $confirmedLastWeek->id], $model::query()->overdue()->pluck('id')->all());
        $this->assertTrue($pendingYesterday->isOverdue());
        $this->assertTrue($confirmedLastWeek->isOverdue());
        foreach ([$today, $completed, $cancelled] as $schedule) {
            $this->assertFalse($schedule->isOverdue());
        }
    }

    /**
     * @param  class-string<TestDrive|ServiceBooking>  $model
     */
    #[DataProvider('schedules')]
    public function test_admin_filter_dan_badge_lewat_jadwal(string $model, string $adminRoute): void
    {
        $overdue = $this->schedule($model, 'pending', '2026-10-14');
        $this->schedule($model, 'pending', '2026-10-16');

        $this->actingAs($this->admin)->get(route($adminRoute))
            ->assertOk()
            ->assertSee('badge-status-red ms-1">Lewat jadwal</span>', false);

        $variable = $model === TestDrive::class ? 'testDrives' : 'bookings';
        $this->get(route($adminRoute, ['jadwal' => 'lewat']))
            ->assertOk()
            ->assertViewHas($variable, fn ($list) => $list->pluck('id')->all() === [$overdue->id]);

        $showRoute = $model === TestDrive::class ? 'admin.test-drives.show' : 'admin.service-bookings.show';
        $this->get(route($showRoute, $overdue))->assertSee('badge-status-red">Lewat jadwal</span>', false);
    }

    /**
     * @param  class-string<TestDrive|ServiceBooking>  $model
     */
    #[DataProvider('schedules')]
    public function test_customer_melihat_keterangan_sesuai_status(string $model, string $adminRoute, string $accountRoute): void
    {
        $this->schedule($model, 'pending', '2026-10-14');

        $this->actingAs($this->customer)->get(route($accountRoute))
            ->assertSee('Jadwal sudah lewat.')
            ->assertSee('atau batalkan lalu booking ulang di jadwal baru')
            ->assertDontSee('untuk mengatur ulang jadwal');

        $model::query()->update(['status' => 'confirmed']);

        $this->get(route($accountRoute))
            ->assertSee('Dealer akan menghubungi Anda untuk mengatur ulang jadwal.')
            ->assertDontSee('atau batalkan lalu booking ulang');
    }

    public function test_dashboard_dan_panel_mekanik(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertDontSee('class="dashboard-attention', false);

        $mechanic = User::factory()->mechanic()->create();
        $this->schedule(TestDrive::class, 'confirmed', '2026-10-13');
        $this->schedule(TestDrive::class, 'pending', '2026-10-14');
        $job = $this->schedule(ServiceBooking::class, 'confirmed', '2026-10-14');
        $job->update(['mechanic_id' => $mechanic->id]);

        $this->get(route('admin.dashboard'))
            ->assertSee('class="dashboard-attention', false)
            ->assertSee('<strong>2</strong> test drive lewat jadwal', false)
            ->assertSee('<strong>1</strong> booking servis lewat jadwal', false)
            ->assertDontSee('pengajuan lewat tenggat bayar')
            ->assertSee(route('admin.test-drives.index', ['jadwal' => 'lewat']), false);

        $this->actingAs($mechanic)->get(route('mechanic.jobs.index'))
            ->assertOk()
            ->assertSee('badge-status-red">Lewat jadwal</span>', false);
    }
}
