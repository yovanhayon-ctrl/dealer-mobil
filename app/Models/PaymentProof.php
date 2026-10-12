<?php

namespace App\Models;

use Database\Factories\PaymentProofFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bukti pembayaran (tanda jadi / DP / pelunasan) untuk pengajuan pembelian yang sudah disetujui.
 * File ada di disk privat dan hanya bisa dibuka pemilik pengajuan atau admin.
 */
#[Fillable([
    'purchase_request_id', 'user_id', 'type', 'amount', 'paid_on', 'file_path', 'file_name', 'note',
    'status', 'admin_note', 'verified_at',
])]
class PaymentProof extends Model
{
    /** @use HasFactory<PaymentProofFactory> */
    use HasFactory;

    /** Disk privat (storage/app/private): bukti transfer berisi data rekening, tidak boleh bisa dibuka publik. */
    public const DISK = 'local';

    public const TYPE_BOOKING_FEE = 'booking_fee';

    public const TYPE_DOWN_PAYMENT = 'down_payment';

    public const TYPE_SETTLEMENT = 'settlement';

    public const TYPE_LABELS = [
        self::TYPE_BOOKING_FEE => 'Tanda jadi',
        self::TYPE_DOWN_PAYMENT => 'DP',
        self::TYPE_SETTLEMENT => 'Pelunasan',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu verifikasi',
        self::STATUS_APPROVED => 'Diterima',
        self::STATUS_REJECTED => 'Ditolak',
    ];

    /** Batas bukti per pengajuan (mencegah unggahan berlebihan). */
    public const MAX_PER_PURCHASE = 10;

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_on' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public static function directory(int $purchaseRequestId): string
    {
        return "payment-proofs/{$purchaseRequestId}";
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isImage(): bool
    {
        return in_array(strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
