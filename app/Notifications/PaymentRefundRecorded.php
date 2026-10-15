<?php

namespace App\Notifications;

use App\Models\PaymentRefund;
use App\Models\PurchaseRequest;
use App\Notifications\Concerns\BuildsNotificationMail;
use Illuminate\Notifications\Notification;

/**
 * Pemberitahuan ke customer tentang dana pengajuan yang batal: pengembalian dicatat, atau ditutup tanpa pengembalian
 * (alasan dari admin). Badge memakai status dana saat itu.
 */
class PaymentRefundRecorded extends Notification
{
    use BuildsNotificationMail;

    private function __construct(private PurchaseRequest $purchaseRequest, private ?PaymentRefund $refund) {}

    public static function refunded(PaymentRefund $refund, PurchaseRequest $purchaseRequest): self
    {
        return new self($purchaseRequest->loadMissing('car.brand', 'paymentProofs', 'paymentRefunds'), $refund);
    }

    public static function closed(PurchaseRequest $purchaseRequest): self
    {
        return new self($purchaseRequest->loadMissing('car.brand', 'paymentProofs', 'paymentRefunds'), null);
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
        $message = PurchaseRequestStatusChanged::describe($request);

        if ($this->refund) {
            $message .= ' · Rp '.self::rupiah($this->refund->amount)." ({$this->refund->methodLabel()}) dikembalikan pada "
                .$this->refund->refunded_on->translatedFormat('d M Y').'.';

            if (($remaining = $request->refundableAmount()) > 0 && $request->refund_closed_at === null) {
                $message .= ' Sisa Rp '.self::rupiah($remaining).' sedang diproses.';
            }
        }

        return [
            'title' => $this->refund ? 'Dana Dikembalikan' : 'Dana Tidak Dikembalikan',
            'message' => $message,
            'status' => $request->paymentStatusBadge(),
            'status_label' => $request->paymentStatusLabel(),
            'admin_note' => $this->refund ? $this->refund->note : $request->refund_closed_note,
            'route' => 'account.purchase-requests.index',
            'anchor' => "pengajuan-{$request->id}",
        ];
    }

    private static function rupiah(int $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }
}
