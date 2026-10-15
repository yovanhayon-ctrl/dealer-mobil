<?php

namespace App\Models;

use Database\Factories\PaymentRefundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dana yang dikembalikan dealer ke customer setelah pengajuan dibatalkan/ditolak. Dicatat admin;
 * file bukti transfer (opsional) di disk privat yang sama dengan bukti pembayaran.
 */
#[Fillable(['purchase_request_id', 'amount', 'refunded_on', 'method', 'file_path', 'file_name', 'note', 'recorded_by'])]
class PaymentRefund extends Model
{
    /** @use HasFactory<PaymentRefundFactory> */
    use HasFactory;

    public const DISK = PaymentProof::DISK;

    public const METHOD_TRANSFER = 'transfer';

    public const METHOD_CASH = 'cash';

    public const METHOD_LABELS = [
        self::METHOD_TRANSFER => 'Transfer',
        self::METHOD_CASH => 'Tunai',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'refunded_on' => 'date',
        ];
    }

    public static function directory(int $purchaseRequestId): string
    {
        return "payment-refunds/{$purchaseRequestId}";
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function hasFile(): bool
    {
        return filled($this->file_path);
    }

    public function isImage(): bool
    {
        return $this->hasFile()
            && in_array(strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    public function methodLabel(): string
    {
        return self::METHOD_LABELS[$this->method] ?? $this->method;
    }
}
