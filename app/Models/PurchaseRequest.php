<?php

namespace App\Models;

use Database\Factories\PurchaseRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'car_id', 'car_price', 'payment_method', 'down_payment', 'tenor_months',
    'interest_rate', 'monthly_installment', 'phone', 'address', 'notes', 'status',
    'admin_note',
])]
class PurchaseRequest extends Model
{
    /** @use HasFactory<PurchaseRequestFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_APPROVED,
        self::STATUS_REJECTED, self::STATUS_COMPLETED, self::STATUS_CANCELLED,
    ];

    /** Label bahasa Indonesia (sama dengan x-status-badge). */
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_PROCESSING => 'Diproses',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_REJECTED => 'Ditolak',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    /**
     * Transisi status yang diizinkan (RANCANGAN §5). Status lain adalah status akhir.
     * Efek stok ditangani App\Actions\ChangePurchaseRequestStatus.
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_PROCESSING => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_APPROVED => [self::STATUS_COMPLETED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
    ];

    /** Status yang wajib disertai catatan admin (alasan untuk customer). */
    public const NOTE_REQUIRED_STATUSES = [self::STATUS_REJECTED, self::STATUS_CANCELLED];

    /** Dihitung "terjual" di laporan: unit sudah dipotong dari stok. */
    public const SOLD_STATUSES = [self::STATUS_APPROVED, self::STATUS_COMPLETED];

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CREDIT = 'credit';

    public const PAYMENT_METHODS = [self::PAYMENT_CASH, self::PAYMENT_CREDIT];

    public const PAYMENT_METHOD_LABELS = [
        self::PAYMENT_CASH => 'Cash',
        self::PAYMENT_CREDIT => 'Kredit',
    ];

    protected function casts(): array
    {
        return [
            'car_price' => 'integer',
            'down_payment' => 'integer',
            'tenor_months' => 'integer',
            'interest_rate' => 'decimal:2',
            'monthly_installment' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCredit(): bool
    {
        return $this->payment_method === self::PAYMENT_CREDIT;
    }

    /**
     * @return array<int, string>
     */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function paymentMethodLabel(): string
    {
        return self::PAYMENT_METHOD_LABELS[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Pokok kredit (harga − DP); untuk cash sama dengan harga.
     */
    public function principal(): int
    {
        return $this->car_price - (int) $this->down_payment;
    }

    /**
     * Total yang dibayar customer: cash = harga; kredit = DP + cicilan × tenor.
     */
    public function totalPayment(): int
    {
        return $this->isCredit()
            ? (int) $this->down_payment + (int) $this->monthly_installment * (int) $this->tenor_months
            : $this->car_price;
    }

    /**
     * Total bunga kredit = total bayar − harga (termasuk selisih pembulatan cicilan).
     */
    public function interestTotal(): int
    {
        return $this->isCredit() ? $this->totalPayment() - $this->car_price : 0;
    }
}
