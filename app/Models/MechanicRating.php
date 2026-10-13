<?php

namespace App\Models;

use Database\Factories\MechanicRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rating 1-5 + komentar dari customer untuk mekanik yang mengerjakan servisnya (satu per booking).
 * Hanya untuk evaluasi internal (owner, admin, mekanik itu sendiri); tidak tampil di halaman publik.
 */
#[Fillable(['service_booking_id', 'mechanic_id', 'user_id', 'rating', 'comment'])]
class MechanicRating extends Model
{
    /** @use HasFactory<MechanicRatingFactory> */
    use HasFactory;

    public const MAX_COMMENT_LENGTH = 500;

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(ServiceBooking::class, 'service_booking_id');
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Rata-rata dengan koma desimal ("4,7"); "-" bila belum ada rating.
     */
    public static function formatAverage(?float $average): string
    {
        return $average === null ? '-' : number_format($average, 1, ',', '.');
    }
}
