<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsNotificationMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan ke customer saat admin mengubah status test drive / pengajuan / booking servis.
 * Disimpan di tabel notifications (channel database), tampil di lonceng navbar & halaman Notifikasi;
 * juga dikirim lewat email bila DEALER_MAIL_NOTIFICATIONS aktif (lihat Notifier).
 *
 * Isi disimpan sebagai salinan (judul, status, catatan) agar riwayat tetap sesuai saat dikirim;
 * tautan disimpan sebagai nama route + anchor sehingga tidak bergantung pada APP_URL.
 */
abstract class StatusChangedNotification extends Notification
{
    use BuildsNotificationMail;

    public function __construct(protected Model $record) {}

    /** Contoh: "Test Drive". */
    abstract protected function subject(): string;

    /** Keterangan singkat data, mis. mobil + jadwal. */
    abstract protected function description(): string;

    /** Route riwayat milik customer. */
    abstract protected function routeName(): string;

    /** Awalan id elemen di halaman riwayat, mis. "test-drive". */
    abstract protected function anchorPrefix(): string;

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
        return [
            'title' => "{$this->subject()} {$this->record->statusLabel()}",
            'message' => $this->description(),
            'status' => $this->record->status,
            'admin_note' => $this->record->admin_note,
            'route' => $this->routeName(),
            'anchor' => "{$this->anchorPrefix()}-{$this->record->getKey()}",
        ];
    }
}
