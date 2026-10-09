<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

/**
 * Kirim notifikasi: selalu ke database (lonceng), lalu email bila DEALER_MAIL_NOTIFICATIONS aktif.
 *
 * Email dikirim terpisah per penerima dan kegagalannya (SMTP mati, kredensial salah) hanya dicatat di log,
 * sehingga booking / perubahan status tetap berhasil dan lonceng tetap terisi untuk semua penerima.
 * Dikirim langsung (tanpa queue) agar demo tidak perlu menjalankan queue:work.
 */
class Notifier
{
    /**
     * @param  mixed  $notifiables  satu user, array, atau collection
     */
    public static function send(mixed $notifiables, Notification $notification): void
    {
        $notifiables = $notifiables instanceof Collection ? $notifiables : collect(is_iterable($notifiables) ? $notifiables : [$notifiables]);

        if ($notifiables->isEmpty()) {
            return;
        }

        NotificationFacade::sendNow($notifiables, $notification, ['database']);

        if (! self::mailEnabled()) {
            return;
        }

        foreach ($notifiables as $notifiable) {
            if (blank($notifiable->email ?? null)) {
                continue;
            }

            try {
                NotificationFacade::sendNow($notifiable, $notification, ['mail']);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    public static function mailEnabled(): bool
    {
        return (bool) config('dealer.mail_notifications');
    }
}
