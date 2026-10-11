<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\TestDrive;
use App\Models\User;
use App\Notifications\Admin\TestDriveActivity;
use App\Notifications\TestDriveStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

/**
 * Notifikasi lewat email (DEALER_MAIL_NOTIFICATIONS): isi email, nyala/mati, dan kegagalan SMTP tidak mengganggu.
 */
class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-08 10:00:00', 'Asia/Jakarta'));
        config(['dealer.mail_notifications' => true]);
        $this->admin = User::factory()->admin()->create(['name' => 'Admin JAF']);
        $this->customer = User::factory()->create(['name' => 'Budi Santoso']);
        $this->car = Car::factory()->create([
            'brand_id' => Brand::factory()->create(['name' => 'Nissan'])->id,
            'name' => 'Kicks e-Power VL', 'year' => 2025, 'vehicle_condition' => Car::CONDITION_NEW, 'stock' => 2,
        ]);
    }

    private function makeTestDrive(): TestDrive
    {
        return TestDrive::factory()->recycle($this->customer)->recycle($this->car)->create([
            'preferred_date' => '2026-10-12', 'preferred_time' => '10:00',
        ]);
    }

    private function confirm(TestDrive $testDrive): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.test-drives.update-status', $testDrive), ['status' => 'confirmed', 'admin_note' => 'Bawa SIM A.'])
            ->assertSessionHas('success');
    }

    public function test_perubahan_status_dikirim_ke_database_dan_email_customer(): void
    {
        Notification::fake();

        $this->confirm($this->makeTestDrive());

        Notification::assertSentTo($this->customer, TestDriveStatusChanged::class, fn ($notification, array $channels) => $channels === ['database']);
        Notification::assertSentTo($this->customer, TestDriveStatusChanged::class, fn ($notification, array $channels) => $channels === ['mail']);
    }

    public function test_booking_baru_dikirim_lewat_email_ke_semua_admin(): void
    {
        Notification::fake();
        $secondAdmin = User::factory()->admin()->create();

        $this->actingAs($this->customer)->post(route('test-drives.store'), [
            'car_id' => $this->car->id, 'preferred_date' => '2026-10-12', 'preferred_time' => '10:00', 'phone' => '081234567890',
        ])->assertSessionHasNoErrors();

        foreach ([$this->admin, $secondAdmin] as $admin) {
            Notification::assertSentTo($admin, TestDriveActivity::class, fn ($notification, array $channels) => $channels === ['mail']);
        }
        Notification::assertNotSentTo($this->customer, TestDriveActivity::class);
    }

    public function test_email_bisa_dimatikan_lewat_env(): void
    {
        config(['dealer.mail_notifications' => false]);
        Notification::fake();

        $this->confirm($this->makeTestDrive());

        Notification::assertSentTo($this->customer, TestDriveStatusChanged::class, fn ($notification, array $channels) => $channels === ['database']);
        Notification::assertNotSentTo($this->customer, TestDriveStatusChanged::class, fn ($notification, array $channels) => in_array('mail', $channels, true));
    }

    public function test_isi_email_customer(): void
    {
        $this->confirm($this->makeTestDrive());
        $stored = $this->customer->notifications()->sole();

        $notification = new TestDriveStatusChanged(TestDrive::sole());
        $notification->id = $stored->id;
        $mail = $notification->toMail($this->customer);

        $this->assertInstanceOf(MailMessage::class, $mail);
        $this->assertSame('Test Drive Dikonfirmasi | JAF Dealer', $mail->subject);
        $this->assertSame('Halo, Budi Santoso', $mail->greeting);
        $this->assertContains('Nissan Kicks e-Power VL 2025 · 12 Okt 2026 pukul 10:00 WIB', $mail->introLines);
        $this->assertContains('Catatan dealer: Bawa SIM A.', $mail->introLines);
        $this->assertSame('Lihat Detail', $mail->actionText);
        $this->assertSame(route('account.notifications.open', $stored->id), $mail->actionUrl);

        // Template email (bahasa Indonesia) bisa dirender tanpa error.
        $html = (string) $mail->render();
        $this->assertStringContainsString('Halo, Budi Santoso', $html);
        $this->assertStringContainsString('Salam,', $html);
    }

    public function test_isi_email_admin_membuka_halaman_admin(): void
    {
        $notification = new TestDriveActivity($this->makeTestDrive());
        $notification->id = '9a1b2c3d-0000-4000-8000-000000000001';
        $mail = $notification->toMail($this->admin);

        $this->assertSame('Test Drive Baru · Budi Santoso | JAF Dealer', $mail->subject);
        $this->assertSame('Halo, Admin JAF', $mail->greeting);
        $this->assertSame('Buka di Admin', $mail->actionText);
        $this->assertSame(route('admin.notifications.open', $notification->id), $mail->actionUrl);
    }

    public function test_tombol_email_menandai_notifikasi_terbaca(): void
    {
        $testDrive = $this->makeTestDrive();
        $this->confirm($testDrive);
        $stored = $this->customer->notifications()->sole();

        $this->actingAs($this->customer)
            ->get(route('account.notifications.open', $stored->id))
            ->assertRedirect(route('account.test-drives.index')."#test-drive-{$testDrive->id}");

        $this->assertNotNull($stored->fresh()->read_at);
    }

    public function test_smtp_gagal_tidak_menggagalkan_proses_dan_lonceng_tetap_terisi(): void
    {
        Exceptions::fake();
        // Port 1 di localhost tidak menerima koneksi: pengiriman email pasti gagal.
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 2,
        ]);
        $secondAdmin = User::factory()->admin()->create();

        $this->actingAs($this->customer)->post(route('test-drives.store'), [
            'car_id' => $this->car->id, 'preferred_date' => '2026-10-12', 'preferred_time' => '10:00', 'phone' => '081234567890',
        ])->assertRedirect(route('account.test-drives.index'))->assertSessionHas('success');

        $this->assertSame(1, TestDrive::count());
        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertSame(1, $secondAdmin->notifications()->count(), 'Kegagalan email untuk admin pertama tidak menghentikan admin berikutnya.');
        // Dua admin = dua email gagal, masing-masing dicatat (report) tanpa menghentikan proses.
        Exceptions::assertReportedCount(2);
        Exceptions::assertReported(TransportException::class);
    }
}
