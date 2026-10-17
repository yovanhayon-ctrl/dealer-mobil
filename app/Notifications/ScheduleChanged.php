<?php

namespace App\Notifications;

use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Notifications\Concerns\BuildsNotificationMail;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan ke customer saat staf menjadwalkan ulang test drive / booking servis (catatan berisi jadwal lama → baru).
 */
class ScheduleChanged extends Notification
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

        return [
            'title' => $isTestDrive ? 'Jadwal Test Drive Diubah' : 'Jadwal Servis Diubah',
            'message' => $isTestDrive ? TestDriveStatusChanged::describe($this->booking) : ServiceBookingStatusChanged::describe($this->booking),
            'status' => $this->booking->status,
            'admin_note' => $this->booking->admin_note,
            'route' => $isTestDrive ? 'account.test-drives.index' : 'account.service-bookings.index',
            'anchor' => ($isTestDrive ? 'test-drive-' : 'servis-').$this->booking->id,
        ];
    }
}
