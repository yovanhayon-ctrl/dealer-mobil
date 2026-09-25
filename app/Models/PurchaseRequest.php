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

    public const STATUSES = ['pending', 'processing', 'approved', 'rejected', 'completed', 'cancelled'];

    public const PAYMENT_METHODS = ['cash', 'credit'];

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
        return $this->status === 'pending';
    }

    public function isCredit(): bool
    {
        return $this->payment_method === 'credit';
    }
}
