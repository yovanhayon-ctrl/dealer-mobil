<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'brand_id', 'category_id', 'name', 'slug', 'vehicle_condition', 'year', 'mileage',
    'price', 'transmission', 'fuel_type', 'engine_cc', 'seats', 'color', 'stock',
    'description', 'is_active',
])]
class Car extends Model
{
    public const CONDITION_NEW = 'baru';

    public const CONDITION_USED = 'bekas';

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'mileage' => 'integer',
            'price' => 'integer',
            'engine_cc' => 'integer',
            'seats' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CarImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(CarImage::class)->where('is_primary', true);
    }

    public function promos(): HasMany
    {
        return $this->hasMany(Promo::class);
    }

    public function activePromos(): HasMany
    {
        return $this->promos()->active();
    }

    public function testDrives(): HasMany
    {
        return $this->hasMany(TestDrive::class);
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isNew(): bool
    {
        return $this->vehicle_condition === self::CONDITION_NEW;
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }
}
