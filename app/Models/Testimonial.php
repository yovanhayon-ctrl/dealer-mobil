<?php

namespace App\Models;

use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'purchase_request_id', 'rating', 'comment', 'status', 'admin_note', 'approved_at'])]
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_APPROVED => 'Disetujui',
        self::STATUS_REJECTED => 'Ditolak',
    ];

    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', self::STATUS_APPROVED);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Customer boleh mengubah selama belum disetujui (menunggu / ditolak).
     */
    public function canBeEditedByCustomer(): bool
    {
        return ! $this->isApproved();
    }

    /**
     * Nama untuk publik: nama depan + inisial nama belakang ("Budi Santoso" → "Budi S.").
     * Email & nomor HP tidak pernah ditampilkan.
     */
    public static function publicName(string $fullName): string
    {
        $parts = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: ['Customer'];
        $first = $parts[0];

        return count($parts) > 1 ? $first.' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.' : $first;
    }

    /** "★★★★☆" untuk teks (notifikasi, email). */
    public static function stars(int $rating): string
    {
        return str_repeat('★', $rating).str_repeat('☆', self::MAX_RATING - $rating);
    }
}
