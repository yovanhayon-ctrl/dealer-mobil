<?php

namespace App\Models;

use Database\Factories\PurchaseRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

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
    /** Status pembayaran (dihitung dari bukti yang diterima admin, tidak disimpan). */
    public const PAYMENT_UNPAID = 'unpaid';

    public const PAYMENT_PARTIAL = 'partial';

    public const PAYMENT_PAID = 'paid';

    /** Peringatan penipuan: tampil di form pengajuan dan PDF bukti pengajuan. */
    public const SAFE_PAYMENT_NOTE = 'Kami tidak pernah meminta pembayaran sebelum Anda cek unit. Transfer hanya ke rekening atas nama dealer.';

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

    /** Tanda bagian catatan yang wajib dilengkapi admin (alasan menolak / membatalkan). */
    public const NOTE_PLACEHOLDER = '…';

    /**
     * Catatan admin otomatis saat status diubah (form admin mengisinya, admin boleh mengubah).
     * Ditolak/Dibatalkan berisi tanda "…" yang wajib diganti alasan sebelum disimpan.
     */
    public const NOTE_TEMPLATES = [
        self::STATUS_PROCESSING => 'Pengajuan Anda sedang kami proses. Tim kami akan menghubungi Anda lewat WhatsApp untuk jadwal cek unit.',
        self::STATUS_APPROVED => 'Pengajuan Anda disetujui. Silakan lakukan pembayaran ke rekening resmi dealer atau langsung di showroom, lalu kirim bukti transfer di menu Pengajuan Saya.',
        self::STATUS_COMPLETED => 'Terima kasih! Mobil telah diserahterimakan. Selamat menikmati kendaraan baru Anda.',
        self::STATUS_REJECTED => 'Mohon maaf, pengajuan Anda belum dapat kami setujui karena '.self::NOTE_PLACEHOLDER,
        self::STATUS_CANCELLED => 'Pengajuan dibatalkan karena '.self::NOTE_PLACEHOLDER,
    ];

    /** Catatan pembatalan bawaan untuk pengajuan yang lewat batas pembayaran pertama. */
    public const PAYMENT_OVERDUE_NOTE = 'Pengajuan dibatalkan karena melewati batas pembayaran; unit dilepas kembali.';

    /** Dihitung "terjual" di laporan: unit sudah dipotong dari stok. */
    public const SOLD_STATUSES = [self::STATUS_APPROVED, self::STATUS_COMPLETED];

    /** Pengajuan yang masih berjalan: customer tidak boleh mengajukan mobil yang sama lagi. */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_APPROVED];

    /** Customer hanya boleh membatalkan saat status ini (RANCANGAN §5); tanpa efek stok. */
    public const CUSTOMER_CANCELLABLE_STATUSES = [self::STATUS_PENDING];

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
            'approved_at' => 'datetime',
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

    public function testimonial(): HasOne
    {
        return $this->hasOne(Testimonial::class);
    }

    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class)->latest()->orderByDesc('id');
    }

    /**
     * Bukti pembayaran hanya bisa dikirim setelah pengajuan disetujui dealer dan selama belum lunas.
     */
    public function canReceivePaymentProofs(): bool
    {
        return $this->status === self::STATUS_APPROVED && ! $this->isPaidOff();
    }

    /**
     * Bagian pembayaran tampil untuk pengajuan disetujui, atau bila sudah ada bukti (riwayat).
     */
    public function hasPaymentSection(): bool
    {
        $this->loadMissing('paymentProofs');

        return $this->status === self::STATUS_APPROVED || $this->paymentProofs->isNotEmpty();
    }

    /**
     * Jenis pembayaran ke dealer: cash = tanda jadi & pelunasan; kredit = tanda jadi & DP (sisanya dibayar leasing).
     *
     * @return array<string, string>
     */
    public function paymentProofTypes(): array
    {
        $types = $this->isCredit()
            ? [PaymentProof::TYPE_BOOKING_FEE, PaymentProof::TYPE_DOWN_PAYMENT]
            : [PaymentProof::TYPE_BOOKING_FEE, PaymentProof::TYPE_SETTLEMENT];

        return array_intersect_key(PaymentProof::TYPE_LABELS, array_flip($types));
    }

    /**
     * Total yang dibayar customer langsung ke dealer: harga mobil (cash) atau DP (kredit).
     */
    public function amountPayableToDealer(): int
    {
        return $this->isCredit() ? (int) $this->down_payment : (int) $this->car_price;
    }

    /**
     * Jumlah bukti pembayaran yang sudah diterima admin.
     */
    public function verifiedPaymentTotal(): int
    {
        $this->loadMissing('paymentProofs');

        return (int) $this->paymentProofs->where('status', PaymentProof::STATUS_APPROVED)->sum('amount');
    }

    public function remainingPayment(): int
    {
        return max(0, $this->amountPayableToDealer() - $this->verifiedPaymentTotal());
    }

    public function isPaidOff(): bool
    {
        return $this->amountPayableToDealer() > 0 && $this->remainingPayment() === 0;
    }

    public function paymentStatus(): string
    {
        return match (true) {
            $this->isPaidOff() => self::PAYMENT_PAID,
            $this->verifiedPaymentTotal() > 0 => self::PAYMENT_PARTIAL,
            default => self::PAYMENT_UNPAID,
        };
    }

    /**
     * Cash: Belum dibayar / Dibayar sebagian / Lunas. Kredit (yang dibayar ke dealer hanya DP): Belum dibayar / DP sebagian / DP lunas.
     */
    public function paymentStatusLabel(): string
    {
        return match ($this->paymentStatus()) {
            self::PAYMENT_PAID => $this->isCredit() ? 'DP lunas' : 'Lunas',
            self::PAYMENT_PARTIAL => $this->isCredit() ? 'DP sebagian' : 'Dibayar sebagian',
            default => 'Belum dibayar',
        };
    }

    /**
     * Warna badge status pembayaran (kunci warna di komponen status-badge).
     */
    public function paymentStatusBadge(): string
    {
        return match ($this->paymentStatus()) {
            self::PAYMENT_PAID => 'completed',
            self::PAYMENT_PARTIAL => 'processing',
            default => 'inactive',
        };
    }

    public function pendingPaymentProofCount(): int
    {
        $this->loadMissing('paymentProofs');

        return $this->paymentProofs->where('status', PaymentProof::STATUS_PENDING)->count();
    }

    public static function paymentDueDays(): int
    {
        return max(1, (int) config('dealer.payment_due_days', 3));
    }

    /**
     * Batas pembayaran pertama: akhir hari ke-N (DEALER_PAYMENT_DUE_DAYS) setelah disetujui; null bila belum disetujui.
     */
    public function paymentDueAt(): ?Carbon
    {
        return $this->approved_at?->copy()->startOfDay()->addDays(self::paymentDueDays())->endOfDay();
    }

    /**
     * Disetujui, ada tagihan ke dealer, dan belum ada pembayaran yang diterima.
     */
    public function awaitsFirstPayment(): bool
    {
        return $this->status === self::STATUS_APPROVED
            && $this->amountPayableToDealer() > 0
            && $this->verifiedPaymentTotal() === 0;
    }

    /**
     * Lewat batas pembayaran pertama tanpa bukti yang sedang diverifikasi. Aturannya sama dengan scope paymentOverdue().
     */
    public function isPaymentOverdue(): bool
    {
        return $this->awaitsFirstPayment()
            && $this->approved_at !== null
            && $this->pendingPaymentProofCount() === 0
            && now()->greaterThan($this->paymentDueAt());
    }

    /**
     * Jumlah hari sejak batas pembayaran terlewati (0 bila belum lewat).
     */
    public function paymentOverdueDays(): int
    {
        return $this->isPaymentOverdue() ? (int) $this->paymentDueAt()->startOfDay()->diffInDays(today()) : 0;
    }

    #[Scope]
    protected function paymentOverdue(Builder $query): void
    {
        $query->where('purchase_requests.status', self::STATUS_APPROVED)
            ->where('purchase_requests.approved_at', '<', today()->subDays(self::paymentDueDays()))
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $cash) => $cash->where('payment_method', self::PAYMENT_CASH)->where('car_price', '>', 0))
                ->orWhere(fn (Builder $credit) => $credit->where('payment_method', self::PAYMENT_CREDIT)->where('down_payment', '>', 0)))
            ->whereDoesntHave('paymentProofs', fn (Builder $proofs) => $proofs
                ->whereIn('status', [PaymentProof::STATUS_APPROVED, PaymentProof::STATUS_PENDING]));
    }

    /**
     * Catatan bawaan per status untuk form admin; pengajuan lewat batas bayar memakai alasan pembatalan yang sudah terisi.
     *
     * @return array<string, string>
     */
    public function noteTemplates(): array
    {
        $templates = self::NOTE_TEMPLATES;

        if ($this->isPaymentOverdue()) {
            $templates[self::STATUS_CANCELLED] = self::PAYMENT_OVERDUE_NOTE;
        }

        return $templates;
    }

    /**
     * Nomor dokumen untuk bukti pengajuan: PB-{tahun pengajuan}-{id 6 digit}, mis. PB-2026-000123.
     */
    public function documentNumber(): string
    {
        return sprintf('PB-%s-%06d', $this->created_at?->format('Y') ?? now()->format('Y'), $this->id);
    }

    /**
     * Ulasan hanya untuk pembelian yang sudah selesai (unit diserahterimakan).
     */
    public function canBeReviewed(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, self::CUSTOMER_CANCELLABLE_STATUSES, true)
            && $this->canTransitionTo(self::STATUS_CANCELLED);
    }

    public function isCredit(): bool
    {
        return $this->payment_method === self::PAYMENT_CREDIT;
    }

    /**
     * Langkah berikutnya untuk customer sesuai status & metode (halaman Pengajuan Saya dan PDF); null untuk status akhir.
     */
    public function nextStep(): ?string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Tim kami akan menghubungi Anda lewat WhatsApp dalam 1 hari kerja.',
            self::STATUS_PROCESSING => $this->isCredit()
                ? 'Siapkan KTP, KK, NPWP, dan slip gaji atau rekening koran untuk survei leasing.'
                : 'Atur jadwal cek unit di showroom. Pembayaran dilakukan setelah Anda cocok dengan unitnya.',
            self::STATUS_APPROVED => $this->isPaidOff()
                ? 'Pembayaran lunas. Tim kami akan menghubungi Anda untuk jadwal serah terima.'
                : ($this->isCredit()
                ? 'Bayar DP dan tanda tangani perjanjian kredit, lalu kirim bukti transfer DP di menu Pengajuan Saya.'
                : 'Lakukan pelunasan di showroom atau transfer ke rekening atas nama dealer, lalu kirim bukti transfernya di menu Pengajuan Saya.'),
            default => null,
        };
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
