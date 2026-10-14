<?php

namespace App\Models;

use Database\Factories\ServiceBookingTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris checklist pekerjaan pada booking servis; dicentang mekanik (selesai) atau dilewati dengan alasan.
 */
#[Fillable(['service_booking_id', 'name', 'position', 'status', 'note', 'checked_at'])]
class ServiceBookingTask extends Model
{
    /** @use HasFactory<ServiceBookingTaskFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DONE = 'done';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_DONE, self::STATUS_SKIPPED];

    public const MAX_NAME_LENGTH = 150;

    public const MAX_NOTE_LENGTH = 255;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'service_booking_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }
}
