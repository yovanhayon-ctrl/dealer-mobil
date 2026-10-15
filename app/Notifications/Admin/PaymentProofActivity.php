<?php

namespace App\Notifications\Admin;

use App\Models\PaymentProof;
use App\Models\PurchaseRequest;
use App\Notifications\PurchaseRequestStatusChanged;

/**
 * Bukti pembayaran baru dari customer yang menunggu verifikasi. Data yang dirujuk adalah pengajuannya,
 * sehingga daftar notifikasi admin menampilkan status pengajuan & pembayaran terkini.
 */
class PaymentProofActivity extends AdminActivityNotification
{
    public function __construct(private PaymentProof $proof, PurchaseRequest $purchaseRequest)
    {
        parent::__construct($purchaseRequest->loadMissing('user:id,name', 'car.brand'));
    }

    protected function subject(): string
    {
        return 'Bukti Pembayaran';
    }

    protected function description(): string
    {
        return PurchaseRequestStatusChanged::describe($this->record)
            ." · {$this->proof->typeLabel()} Rp ".number_format($this->proof->amount, 0, ',', '.')." ({$this->proof->methodLabel()})";
    }

    public function permission(): string
    {
        return 'payments.manage';
    }

    protected function routeName(): string
    {
        return 'admin.purchase-requests.show';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Langsung ke bagian bukti pembayaran di detail pengajuan.
        return [...parent::toArray($notifiable), 'anchor' => 'bukti-pembayaran'];
    }
}
