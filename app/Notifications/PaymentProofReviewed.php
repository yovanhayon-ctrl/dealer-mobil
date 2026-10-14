<?php

namespace App\Notifications;

use App\Models\PaymentProof;
use App\Models\PurchaseRequest;
use App\Notifications\Concerns\BuildsNotificationMail;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan ke customer saat pembayarannya diterima (bukti transfer disetujui atau pembayaran showroom dicatat)
 * atau bukti transfernya ditolak beserta alasan. Badge memakai status pembayaran saat itu (Sebagian / Lunas / Ditolak).
 */
class PaymentProofReviewed extends Notification
{
    use BuildsNotificationMail;

    public function __construct(private PaymentProof $proof, private PurchaseRequest $purchaseRequest)
    {
        $this->purchaseRequest->loadMissing('car.brand', 'paymentProofs');
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
        $payment = PurchaseRequestStatusChanged::describe($request)
            ." · {$this->proof->typeLabel()} Rp ".self::rupiah($this->proof->amount)." ({$this->proof->methodLabel()})";
        $approved = $this->proof->status === PaymentProof::STATUS_APPROVED;

        if ($approved) {
            $payment .= ' diterima. '.($request->isPaidOff()
                ? "{$request->paymentStatusLabel()}."
                : 'Sisa '.($request->isCredit() ? 'DP ' : '').'Rp '.self::rupiah($request->remainingPayment()).'.');
        }

        return [
            'title' => $approved ? 'Pembayaran Diterima' : 'Bukti Pembayaran Ditolak',
            'message' => $payment,
            'status' => $approved ? $request->paymentStatusBadge() : 'rejected',
            'status_label' => $approved ? $request->paymentStatusLabel() : 'Ditolak',
            'admin_note' => $approved ? null : $this->proof->admin_note,
            'route' => 'account.purchase-requests.index',
            'anchor' => "pengajuan-{$request->id}",
        ];
    }

    private static function rupiah(int $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }
}
