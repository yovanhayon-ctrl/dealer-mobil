<?php

namespace App\Notifications\Admin;

use App\Models\User;
use App\Notifications\Concerns\BuildsNotificationMail;
use App\Notifications\Notifier;
use Illuminate\Notifications\Notification;

/**
 * Ke owner lain saat rekening pembayaran dealer diubah di Admin > Pengaturan, karena rekening ini yang dipakai customer
 * untuk transfer (form bukti pembayaran, pengingat batas bayar, PDF pengajuan).
 */
class BankAccountChanged extends Notification
{
    use BuildsNotificationMail;

    /**
     * @param  array{name: ?string, account: ?string, holder: ?string}  $old
     * @param  array{name: ?string, account: ?string, holder: ?string}  $new
     */
    public function __construct(private User $actor, private array $old, private array $new) {}

    /**
     * Kirim ke semua owner aktif selain pengubahnya.
     */
    public static function notifyOwners(self $notification): void
    {
        $recipients = User::where('role', User::ROLE_OWNER)->where('is_active', true)
            ->whereKeyNot($notification->actor->getKey())->get();

        Notifier::send($recipients, $notification);
    }

    /**
     * Contoh: "BCA 1234567890 a.n. PT JAF Dealer"; kosong: "belum diisi".
     *
     * @param  array{name: ?string, account: ?string, holder: ?string}  $bank
     */
    public static function describe(array $bank): string
    {
        return filled($bank['account'] ?? null)
            ? trim("{$bank['name']} {$bank['account']}").(filled($bank['holder'] ?? null) ? " a.n. {$bank['holder']}" : '')
            : 'belum diisi';
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

    protected function mailActionText(): string
    {
        return 'Buka Pengaturan';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Rekening Pembayaran Diubah · {$this->actor->name}",
            'message' => 'Rekening sekarang: '.self::describe($this->new).'. Sebelumnya: '.self::describe($this->old).'.',
            'admin_note' => 'Pastikan rekening ini benar: customer mentransfer pembayaran ke rekening ini.',
            'route' => 'admin.settings.edit',
            'params' => [],
            'anchor' => 'rekening',
        ];
    }
}
