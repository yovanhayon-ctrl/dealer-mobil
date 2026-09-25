<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'car_id', 'title', 'slug', 'description', 'image', 'discount_amount',
    'start_date', 'end_date', 'is_active',
])]
class Promo extends Model
{
    protected function casts(): array
    {
        return [
            'discount_amount' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Promo aktif: is_active = true dan hari ini di antara start_date dan end_date.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $today = today()->toDateString();

        $query->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }
}
