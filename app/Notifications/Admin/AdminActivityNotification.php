<?php

namespace App\Notifications\Admin;

use App\Models\User;
use App\Notifications\Concerns\BuildsNotificationMail;
use App\Notifications\Notifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan ke semua admin saat customer membuat atau membatalkan test drive / pengajuan / booking servis.
 * Format data sama dengan notifikasi customer (judul, pesan, status, route) ditambah parameter route,
 * sehingga tautannya langsung membuka halaman detail di admin.
 */
abstract class AdminActivityNotification extends Notification
{
    use BuildsNotificationMail;

    public const CREATED = 'created';

    public const CANCELLED = 'cancelled';

    public const UPDATED = 'updated';

    public function __construct(protected Model $record, protected string $event = self::CREATED) {}

    /** Contoh: "Test Drive". */
    abstract protected function subject(): string;

    /** Keterangan singkat data (mobil/layanan + jadwal). */
    abstract protected function description(): string;

    /** Route detail di admin, menerima id data. */
    abstract protected function routeName(): string;

    /**
     * Kirim ke semua akun admin.
     */
    public static function notifyAdmins(self $notification): void
    {
        Notifier::send(User::where('role', User::ROLE_ADMIN)->get(), $notification);
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
        return 'admin.notifications.open';
    }

    /**
     * Parameter route tujuan; bawaan id data (halaman detail admin).
     *
     * @return array<int|string, mixed>
     */
    protected function routeParams(): array
    {
        return [$this->record->getKey()];
    }

    protected function mailActionText(): string
    {
        return 'Buka di Admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $customer = $this->record->user->name;
        $title = match ($this->event) {
            self::CANCELLED => "{$this->subject()} Dibatalkan Customer",
            self::UPDATED => "{$this->subject()} Diperbarui",
            default => "{$this->subject()} Baru",
        };

        return [
            'title' => "{$title} · {$customer}",
            'message' => $this->description(),
            'status' => $this->event === self::CANCELLED ? 'cancelled' : $this->record->status,
            'admin_note' => null,
            'route' => $this->routeName(),
            'params' => $this->routeParams(),
        ];
    }
}
