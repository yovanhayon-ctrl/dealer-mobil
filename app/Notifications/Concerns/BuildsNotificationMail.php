<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email dari data notifikasi yang sama dengan lonceng (judul, pesan, catatan dealer).
 * Tombol membuka route "buka notifikasi" sehingga notifikasinya ikut tertandai terbaca.
 */
trait BuildsNotificationMail
{
    /** Route "buka notifikasi" (customer / admin), menerima id notifikasi. */
    abstract protected function openRoute(): string;

    /** Label tombol di email. */
    protected function mailActionText(): string
    {
        return 'Lihat Detail';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);
        $dealer = config('dealer.name');

        $mail = (new MailMessage)
            ->subject("{$data['title']} — {$dealer}")
            ->greeting("Halo, {$notifiable->name}")
            ->line("**{$data['title']}**")
            ->line($data['message']);

        if (! empty($data['admin_note'])) {
            $mail->line("Catatan dealer: {$data['admin_note']}");
        }

        return $mail
            ->action($this->mailActionText(), route($this->openRoute(), $this->id))
            ->salutation("Salam,\n{$dealer}");
    }
}
