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
    'purchase_request_id', 'user_id', 'recorded_by', 'type', 'method', 'amount', 'paid_on', 'file_path', 'file_name', 'note',
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

    /** Transfer dengan bukti dari customer (bawaan); lainnya dicatat admin saat bayar di showroom. */
    public const METHOD_TRANSFER = 'transfer';

    public const METHOD_CASH = 'cash';

    public const METHOD_CARD = 'card';

    public const METHOD_ONSITE_TRANSFER = 'onsite_transfer';

    public const SHOWROOM_METHODS = [self::METHOD_CASH, self::METHOD_CARD, self::METHOD_ONSITE_TRANSFER];

    public const METHOD_LABELS = [
        self::METHOD_TRANSFER => 'Transfer',
        self::METHOD_CASH => 'Tunai di showroom',
        self::METHOD_CARD => 'Debit/kartu kredit (EDC) di showroom',
        self::METHOD_ONSITE_TRANSFER => 'Transfer di showroom',
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

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Dibayar langsung di showroom dan dicatat admin (bukan bukti unggahan customer).
     */
    public function isShowroomPayment(): bool
    {
        return in_array($this->method, self::SHOWROOM_METHODS, true);
    }

    public function hasFile(): bool
    {
        return filled($this->file_path);
    }

    public function methodLabel(): string
    {
        return self::METHOD_LABELS[$this->method] ?? $this->method;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isImage(): bool
    {
        return $this->hasFile()
            && in_array(strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
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
