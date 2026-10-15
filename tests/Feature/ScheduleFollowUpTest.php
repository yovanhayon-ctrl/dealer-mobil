<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Models\User;
use App\Notifications\MechanicJobAssigned;
use App\Notifications\ScheduleChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tindak lanjut jadwal & pengajuan: status Tidak datang, jadwal ulang oleh staf, dan pengajuan belum ditanggapi.
 */
class ScheduleFollowUpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Kamis, 15 Okt 2026.
        $this->travelTo(Carbon::parse('2026-10-15 10:00:00', 'Asia/Jakarta'));
        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->create();
    }

    private function makeTestDrive(string $status, string $date, array $attributes = []): TestDrive
    {
        return TestDrive::factory()->recycle($this->customer)->status($status)->create([
            'preferred_date' => $date, 'preferred_time' => '10:00', ...$attributes,
        ]);
    }

    private function serviceBooking(string $status, string $date, array $attributes = []): ServiceBooking
    {
        return ServiceBooking::factory()->recycle($this->customer)->status($status)->create([
            'preferred_date' => $date, 'preferred_time' => '10:00', ...$attributes,
        ]);
    }

    public function test_tidak_datang_hanya_dari_dikonfirmasi_pada_atau_sesudah_tanggal_jadwal(): void
    {
        $future = $this->makeTestDrive('confirmed', '2026-10-16');
        $pendingPast = $this->makeTestDrive('pending', '2026-10-14');
        $confirmedToday = $this->makeTestDrive('confirmed', '2026-10-15');

        $this->assertNotContains('no_show', $future->allowedTransitions());
        $this->assertNotContains('no_show', $pendingPast->allowedTransitions());
        $this->assertContains('no_show', $confirmedToday->allowedTransitions());

        $this->actingAs($this->admin)->patch(route('admin.test-drives.update-status', $future), ['status' => 'no_show'])
            ->assertSessionHasErrors('status');
        $this->assertSame('confirmed', $future->fresh()->status);

        // Catatan dikosongkan: terisi otomatis untuk customer.
        $this->patch(route('admin.test-drives.update-status', $confirmedToday), ['status' => 'no_show', 'admin_note' => ''])
            ->assertSessionHasNoErrors();
        $confirmedToday->refresh();
        $this->assertSame('no_show', $confirmedToday->status);
        $this->assertSame('Anda tidak hadir pada jadwal 15 Okt 2026 pukul 10:00 WIB. Silakan booking ulang bila masih berminat.', $confirmedToday->admin_note);
        $this->assertSame('Test Drive Tidak datang', $this->customer->notifications()->sole()->data['title']);
        $this->assertFalse($confirmedToday->isOverdue());

        $this->actingAs($this->customer)->get(route('account.test-drives.index', ['status' => 'dibatalkan']))
            ->assertSee('Tidak datang');
    }

    public function test_servis_tidak_datang_hilang_dari_pekerjaan_mekanik_dan_melepas_slot(): void
    {
        $mechanic = User::factory()->mechanic()->create();
        $booking = $this->serviceBooking('confirmed', '2026-10-14', ['mechanic_id' => $mechanic->id]);

        $this->actingAs($this->admin)->get(route('admin.service-bookings.show', $booking))
            ->assertSee('value="no_show"', false);
        $this->patch(route('admin.service-bookings.update-status', $booking), ['status' => 'no_show'])->assertSessionHasNoErrors();

        $this->assertSame('no_show', $booking->fresh()->status);
        $this->assertSame(0, ServiceBooking::query()->active()->count());
        $this->actingAs($mechanic)->get(route('mechanic.jobs.index'))->assertDontSee($booking->plate_number);
    }

    public function test_jadwal_ulang_test_drive_menjadi_dikonfirmasi_dan_customer_diberi_tahu(): void
    {
        $testDrive = $this->makeTestDrive('pending', '2026-10-13');

        $this->actingAs($this->admin)->get(route('admin.test-drives.show', $testDrive))->assertSee('Jadwalkan Ulang');

        $this->patch(route('admin.test-drives.reschedule', $testDrive), ['preferred_date' => '2026-10-17', 'preferred_time' => '14:00'])
            ->assertRedirect(route('admin.test-drives.show', $testDrive))
            ->assertSessionHas('success', 'Jadwal diubah ke 17 Okt 2026 14:00 WIB. Customer sudah diberi tahu.');

        $testDrive->refresh();
        $this->assertSame('confirmed', $testDrive->status);
        $this->assertSame('2026-10-17', $testDrive->preferred_date->toDateString());
        $this->assertSame('14:00', $testDrive->timeLabel());
        $this->assertSame('Jadwal diubah dari 13 Okt 2026 10:00 ke 17 Okt 2026 14:00 WIB.', $testDrive->admin_note);
        $this->assertFalse($testDrive->isOverdue());

        $notification = $this->customer->notifications()->sole();
        $this->assertSame(ScheduleChanged::class, $notification->type);
        $this->assertSame('Jadwal Test Drive Diubah', $notification->data['title']);
        $this->assertSame("test-drive-{$testDrive->id}", $notification->data['anchor']);
    }

    public function test_validasi_jadwal_ulang(): void
    {
        $car = Car::factory()->create(['stock' => 2]);
        $testDrive = $this->makeTestDrive('confirmed', '2026-10-16', ['car_id' => $car->id]);
        $this->makeTestDrive('confirmed', '2026-10-17', ['car_id' => $car->id, 'preferred_time' => '11:00']);
        $url = route('admin.test-drives.reschedule', $testDrive);

        $this->actingAs($this->admin);
        $this->patch($url, ['preferred_date' => '2026-10-15', 'preferred_time' => '14:00'])
            ->assertSessionHasErrorsIn('reschedule', ['preferred_date' => 'Tanggal baru paling cepat besok.']);
        $this->patch($url, ['preferred_date' => '2026-10-16', 'preferred_time' => '10:00'])
            ->assertSessionHasErrorsIn('reschedule', ['preferred_time' => 'Jadwal baru sama dengan jadwal sekarang.']);
        $this->patch($url, ['preferred_date' => '2026-10-17', 'preferred_time' => '11:00'])
            ->assertSessionHasErrorsIn('reschedule', 'preferred_time');
        $this->patch($url, ['preferred_date' => '2026-10-17', 'preferred_time' => '07:00'])
            ->assertSessionHasErrorsIn('reschedule', 'preferred_time');
        $this->assertSame('2026-10-16', $testDrive->fresh()->preferred_date->toDateString());

        // Sudah selesai: tidak bisa dijadwalkan ulang.
        $done = $this->makeTestDrive('completed', '2026-10-10');
        $this->get(route('admin.test-drives.show', $done))->assertDontSee('Jadwalkan Ulang');
        $this->patch(route('admin.test-drives.reschedule', $done), ['preferred_date' => '2026-10-20', 'preferred_time' => '10:00'])
            ->assertSessionHas('error', 'Jadwal tidak bisa diubah karena statusnya sudah Selesai.');
    }

    public function test_jadwal_ulang_servis_mengikuti_kapasitas_dan_memberi_tahu_mekanik(): void
    {
        config(['dealer.service_slot_capacity' => 1]);
        $mechanic = User::factory()->mechanic()->create();
        $booking = $this->serviceBooking('confirmed', '2026-10-14', ['mechanic_id' => $mechanic->id]);
        $this->serviceBooking('pending', '2026-10-19', ['preferred_time' => '09:00']);

        $this->actingAs($this->admin)
            ->patch(route('admin.service-bookings.reschedule', $booking), ['preferred_date' => '2026-10-19', 'preferred_time' => '09:00'])
            ->assertSessionHasErrorsIn('reschedule', ['preferred_time' => 'Slot bengkel pada jam itu sudah penuh. Pilih jam atau tanggal lain.']);

        $this->patch(route('admin.service-bookings.reschedule', $booking), ['preferred_date' => '2026-10-19', 'preferred_time' => '13:00'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-10-19', $booking->fresh()->preferred_date->toDateString());
        $this->assertSame('Jadwal Servis Diubah', $this->customer->notifications()->sole()->data['title']);
        $mechanicNotification = $mechanic->notifications()->sole();
        $this->assertSame(MechanicJobAssigned::class, $mechanicNotification->type);
        $this->assertSame('Jadwal Servis Diubah', $mechanicNotification->data['title']);
    }

    public function test_hak_jadwal_ulang_mengikuti_menu(): void
    {
        $testDrive = $this->makeTestDrive('pending', '2026-10-16');
        $booking = $this->serviceBooking('pending', '2026-10-16');
        $payload = ['preferred_date' => '2026-10-20', 'preferred_time' => '10:00'];

        $this->actingAs(User::factory()->sales()->create())->patch(route('admin.test-drives.reschedule', $testDrive), $payload)->assertSessionHasNoErrors();
        $this->patch(route('admin.service-bookings.reschedule', $booking), $payload)->assertForbidden();

        $this->actingAs(User::factory()->customerService()->create())->patch(route('admin.service-bookings.reschedule', $booking), $payload)->assertSessionHasNoErrors();
        $this->assertSame('2026-10-20', $booking->fresh()->preferred_date->toDateString());

        $this->actingAs($this->customer)->patch(route('admin.test-drives.reschedule', $testDrive), $payload)->assertForbidden();
    }

    public function test_pengajuan_menunggu_lebih_dari_dua_hari_ditandai_belum_ditanggapi(): void
    {
        $old = PurchaseRequest::factory()->status('pending')->create(['created_at' => now()->subDays(3)]);
        $fresh = PurchaseRequest::factory()->status('pending')->create(['created_at' => now()->subDay()]);
        $processing = PurchaseRequest::factory()->status('processing')->create(['created_at' => now()->subDays(5)]);

        $this->assertTrue($old->isAwaitingResponse());
        $this->assertFalse($fresh->isAwaitingResponse());
        $this->assertFalse($processing->isAwaitingResponse());
        $this->assertSame([$old->id], PurchaseRequest::query()->awaitingResponse()->pluck('id')->all());

        $this->actingAs($this->admin)->get(route('admin.purchase-requests.index', ['tindak' => 'belum-ditanggapi']))
            ->assertViewHas('purchaseRequests', fn ($list) => $list->pluck('id')->all() === [$old->id])
            ->assertSee('badge-status-red ms-1">Belum ditanggapi</span>', false);
        $this->get(route('admin.dashboard'))->assertSee('<strong>1</strong> pengajuan belum ditanggapi', false);

        // Sales memproses pengajuan: ikut melihat; Customer Service tidak.
        $this->actingAs(User::factory()->sales()->create())->get(route('admin.dashboard'))->assertSee('pengajuan belum ditanggapi');
        $this->actingAs(User::factory()->customerService()->create())->get(route('admin.dashboard'))->assertDontSee('pengajuan belum ditanggapi');
    }
}
