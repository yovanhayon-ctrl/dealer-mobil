<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Models\User;
use App\Notifications\MechanicJobAssigned;
use App\Notifications\ScheduleReminder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Pengingat H-1 test drive & servis: reminders:send (scheduler), tombol dashboard, dan tombol WhatsApp manual.
 */
class ScheduleReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        // Selasa, 20 Okt 2026: "besok" = 21 Okt.
        $this->travelTo(Carbon::parse('2026-10-20 16:00:00', 'Asia/Jakarta'));
        config(['dealer' => [...config('dealer'), 'name' => 'JAF Dealer', 'address' => 'BSD Junction, Serpong', 'whatsapp' => '6281200008888']]);
        $this->customer = User::factory()->create(['name' => 'Fadlan']);
        $this->car = Car::factory()->create(['brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id, 'name' => 'Kicks e-Power VL']);
    }

    private function makeTestDrive(string $status, string $date, array $attributes = []): TestDrive
    {
        return TestDrive::factory()->recycle($this->customer)->recycle($this->car)->status($status)->create([
            'preferred_date' => $date, 'preferred_time' => '10:00', 'phone' => '082258098791', ...$attributes,
        ]);
    }

    public function test_hanya_jadwal_dikonfirmasi_besok_yang_diingatkan_sekali(): void
    {
        $tomorrow = $this->makeTestDrive('confirmed', '2026-10-21');
        $later = $this->makeTestDrive('confirmed', '2026-10-22');
        $pending = $this->makeTestDrive('pending', '2026-10-21', ['preferred_time' => '11:00']);
        $cancelled = $this->makeTestDrive('cancelled', '2026-10-21', ['preferred_time' => '12:00']);
        $already = $this->makeTestDrive('confirmed', '2026-10-21', ['preferred_time' => '13:00', 'reminded_at' => now()->subHour()]);

        $this->artisan('reminders:send')
            ->expectsOutput('Pengingat terkirim: 1 test drive, 0 servis, 0 ke mekanik.')
            ->assertSuccessful();

        $notification = $this->customer->notifications()->sole();
        $this->assertSame(ScheduleReminder::class, $notification->type);
        $this->assertSame('Pengingat: Test Drive Besok', $notification->data['title']);
        $this->assertStringContainsString('Bawa SIM yang masih berlaku.', $notification->data['admin_note']);
        $this->assertStringContainsString('BSD Junction, Serpong', $notification->data['admin_note']);
        $this->assertStringContainsString('WhatsApp 6281200008888', $notification->data['admin_note']);
        $this->assertSame("test-drive-{$tomorrow->id}", $notification->data['anchor']);

        $this->assertTrue($tomorrow->fresh()->reminded_at->equalTo(now()));
        foreach ([$later, $pending, $cancelled] as $testDrive) {
            $this->assertNull($testDrive->fresh()->reminded_at);
        }
        $this->assertTrue($already->fresh()->reminded_at->equalTo(now()->subHour()));

        // Dijalankan lagi (cron & tombol): tidak ada pengingat ganda.
        $this->artisan('reminders:send')->expectsOutput('Pengingat terkirim: 0 test drive, 0 servis, 0 ke mekanik.');
        $this->assertSame(1, $this->customer->notifications()->count());
    }

    public function test_servis_mengingatkan_customer_dan_mekanik(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $booking = ServiceBooking::factory()->recycle($this->customer)->status('confirmed')->create([
            'service_id' => Service::factory()->create(['name' => 'Ban & Aki'])->id,
            'preferred_date' => '2026-10-21', 'preferred_time' => '09:00', 'mechanic_id' => $mechanic->id,
        ]);

        $this->artisan('reminders:send')->expectsOutput('Pengingat terkirim: 0 test drive, 1 servis, 1 ke mekanik.');

        $customerNotification = $this->customer->notifications()->sole();
        $this->assertSame('Pengingat: Servis Besok', $customerNotification->data['title']);
        $this->assertStringContainsString('Mohon datang 15 menit lebih awal.', $customerNotification->data['admin_note']);
        $this->assertSame("servis-{$booking->id}", $customerNotification->data['anchor']);

        $mechanicNotification = $mechanic->notifications()->sole();
        $this->assertSame(MechanicJobAssigned::class, $mechanicNotification->type);
        $this->assertSame('Pekerjaan Servis Besok', $mechanicNotification->data['title']);
    }

    public function test_dry_run_tidak_mengirim(): void
    {
        $testDrive = $this->makeTestDrive('confirmed', '2026-10-21');

        $this->artisan('reminders:send --dry-run')
            ->expectsOutput("Test drive #{$testDrive->id} 10:00 · Fadlan · Kicks e-Power VL")
            ->expectsOutput('Akan diingatkan: 1 test drive, 0 servis (belum dikirim).')
            ->assertSuccessful();

        $this->assertSame(0, $this->customer->notifications()->count());
        $this->assertNull($testDrive->fresh()->reminded_at);
    }

    public function test_jadwal_ulang_mengosongkan_tanda_pengingat(): void
    {
        $testDrive = $this->makeTestDrive('confirmed', '2026-10-21', ['reminded_at' => now()]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.test-drives.reschedule', $testDrive), ['preferred_date' => '2026-10-23', 'preferred_time' => '14:00'])
            ->assertSessionHasNoErrors();

        $this->assertNull($testDrive->fresh()->reminded_at);
    }

    public function test_tombol_dashboard_untuk_peran_yang_berhak(): void
    {
        $this->makeTestDrive('confirmed', '2026-10-21');
        $this->makeTestDrive('pending', '2026-10-21', ['preferred_time' => '11:00']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertSee(route('admin.reminders.send'), false)
            ->assertSee('<strong>1</strong> test drive besok belum dikonfirmasi', false);

        $this->post(route('admin.reminders.send'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success', 'Pengingat terkirim: 1 test drive dan 0 servis.');
        $this->post(route('admin.reminders.send'))
            ->assertSessionHas('status', 'Tidak ada jadwal besok yang perlu diingatkan (belum dikonfirmasi atau sudah diingatkan).');

        $sales = User::factory()->sales()->create();
        $this->actingAs($sales)->get(route('admin.dashboard'))->assertDontSee(route('admin.reminders.send'), false);
        $this->post(route('admin.reminders.send'))->assertForbidden();
    }

    public function test_detail_admin_menampilkan_status_pengingat_dan_tombol_whatsapp(): void
    {
        $testDrive = $this->makeTestDrive('confirmed', '2026-10-21', ['reminded_at' => now()]);
        $done = $this->makeTestDrive('completed', '2026-10-10');

        $html = $this->actingAs(User::factory()->admin()->create())->get(route('admin.test-drives.show', $testDrive))
            ->assertSee('Pengingat H-1 terkirim 20 Okt 2026 16:00')
            ->assertSee('Ingatkan via WhatsApp')
            ->getContent();
        $this->assertStringContainsString('https://wa.me/6282258098791?text=', $html);
        $this->assertStringContainsString(rawurlencode('Halo Fadlan, kami dari JAF Dealer mengingatkan jadwal test drive Nissan Kicks e-Power VL'), $html);

        $this->get(route('admin.test-drives.show', $done))->assertDontSee('Ingatkan via WhatsApp');
    }

    public function test_dijadwalkan_setiap_hari_sesuai_jam_konfigurasi(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'reminders:send'));

        $this->assertNotNull($event);
        $this->assertSame('0 16 * * *', $event->expression);
    }
}
