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

    public const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

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
        return $this->status === 'pending';
    }
}
