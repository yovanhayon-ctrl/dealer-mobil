<?php

namespace App\Notifications;

use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Notifications\Concerns\BuildsNotificationMail;
use Illuminate\Notifications\Notification;

/**
 * Pengingat H-1 ke customer untuk test drive / booking servis yang sudah dikonfirmasi (lihat SendScheduleReminders).
 */
class ScheduleReminder extends Notification
{
    use BuildsNotificationMail;

    public function __construct(private TestDrive|ServiceBooking $booking)
    {
        $booking instanceof TestDrive ? $booking->loadMissing('car.brand') : $booking->loadMissing('service');
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return Notifier::mailEnabled() ? ['database', 'mail'] : ['database'];
    }

    protected function openRoute(): string
    {
        return 'account.notifications.open';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isTestDrive = $this->booking instanceof TestDrive;
        $dealer = config('dealer');

        $note = $isTestDrive ? 'Bawa SIM yang masih berlaku.' : 'Mohon datang 15 menit lebih awal.';
        if (filled($dealer['address'])) {
            $note .= " Lokasi: {$dealer['address']}.";
        }
        if (filled($dealer['whatsapp'])) {
            $note .= " Berhalangan? Hubungi kami lewat WhatsApp {$dealer['whatsapp']}.";
        }

        return [
            'title' => $isTestDrive ? 'Pengingat: Test Drive Besok' : 'Pengingat: Servis Besok',
            'message' => $isTestDrive ? TestDriveStatusChanged::describe($this->booking) : ServiceBookingStatusChanged::describe($this->booking),
            'status' => $this->booking->status,
            'admin_note' => $note,
            'route' => $isTestDrive ? 'account.test-drives.index' : 'account.service-bookings.index',
            'anchor' => ($isTestDrive ? 'test-drive-' : 'servis-').$this->booking->id,
        ];
    }
}
