<?php

namespace App\Notifications;

use App\Models\ServiceBooking;
use App\Notifications\Concerns\BuildsNotificationMail;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan ke mekanik saat ada pekerjaan baru di Pekerjaan Saya: booking yang ditugaskan kepadanya dikonfirmasi,
 * atau booking yang sudah dikonfirmasi dialihkan kepadanya.
 */
class MechanicJobAssigned extends Notification
{
    use BuildsNotificationMail;

    public function __construct(private ServiceBooking $booking)
    {
        $this->booking->loadMissing('service');
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
        return 'mechanic.notifications.open';
    }

    protected function mailActionText(): string
    {
        return 'Buka Pekerjaan';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Pekerjaan Servis Baru',
            'message' => ServiceBookingStatusChanged::describe($this->booking),
            'status' => $this->booking->status,
            'admin_note' => null,
            'route' => 'mechanic.jobs.show',
            'params' => [$this->booking->id],
        ];
    }
}
