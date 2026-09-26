<?php

namespace App\Models;

use Database\Factories\TestDriveFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'car_id', 'preferred_date', 'preferred_time', 'phone', 'notes',
    'status', 'admin_note',
])]
class TestDrive extends Model
{
    /** @use HasFactory<TestDriveFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_COMPLETED, self::STATUS_CANCELLED];

    /** Label bahasa Indonesia (sama dengan x-status-badge). */
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_CONFIRMED => 'Dikonfirmasi',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    /** Transisi status yang diizinkan (RANCANGAN §5). Status lain adalah status akhir. */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
        self::STATUS_CONFIRMED => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
    ];

    /** Status yang wajib disertai catatan admin (alasan untuk customer). */
    public const NOTE_REQUIRED_STATUSES = [self::STATUS_CANCELLED];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
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

    /**
     * Jam jadwal "HH:MM" (kolom time bisa berisi detik).
     */
    public function timeLabel(): string
    {
        return substr((string) $this->preferred_time, 0, 5);
    }
}
