<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;
use App\Notifications\Concerns\BuildsNotificationMail;
use Illuminate\Notifications\Notification;

/**
 * Pengingat H-1 batas pembayaran pertama ke customer (pengajuan Disetujui yang belum dibayar, lihat SendScheduleReminders).
 */
class PaymentDueReminder extends Notification
{
    use BuildsNotificationMail;

    public function __construct(private PurchaseRequest $purchaseRequest)
    {
        $this->purchaseRequest->loadMissing('car.brand');
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
        $request = $this->purchaseRequest;

        return [
            'title' => 'Pengingat: Batas Pembayaran Besok',
            'message' => PurchaseRequestStatusChanged::describe($request)
                .'. Lakukan pembayaran pertama (tanda jadi, DP, atau pelunasan) paling lambat '
                .$request->paymentDueAt()->translatedFormat('l, d M Y').'.',
            'status' => $request->status,
            'admin_note' => self::howToPay(),
            'route' => 'account.purchase-requests.index',
            'anchor' => "pengajuan-{$request->id}",
        ];
    }

    /**
     * Cara bayar: rekening resmi (bila diisi di .env), showroom, lalu kirim bukti. Dipakai juga pesan WhatsApp admin.
     */
    public static function howToPay(): string
    {
        $bank = config('dealer.bank');
        $transfer = filled($bank['account'])
            ? "Transfer ke {$bank['name']} {$bank['account']} a.n. {$bank['holder']}"
            : 'Transfer ke rekening resmi dealer';

        return "{$transfer} atau bayar langsung di showroom, lalu kirim bukti transfer di menu Pengajuan Saya.";
    }
}
