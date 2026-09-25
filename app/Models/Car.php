<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Database\Factories\CarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    /** @use HasFactory<CarFactory> */
    use HasFactory, HasUniqueSlug;

    public const CONDITION_NEW = 'baru';

    public const CONDITION_USED = 'bekas';

    /** Nilai enum => label bahasa Indonesia. */
    public const CONDITIONS = [
        self::CONDITION_NEW => 'Baru',
        self::CONDITION_USED => 'Bekas',
    ];

    public const TRANSMISSIONS = [
        'manual' => 'Manual',
        'automatic' => 'Otomatis',
    ];

    public const FUEL_TYPES = [
        'bensin' => 'Bensin',
        'diesel' => 'Diesel',
        'hybrid' => 'Hybrid',
        'listrik' => 'Listrik',
    ];

    /**
     * Relasi yang membuat mobil tidak boleh dihapus (test drive & pengajuan: FK restrict;
     * promo: FK nullOnDelete akan mengubah promo khusus mobil menjadi promo umum).
     */
    public const DELETION_BLOCKERS = ['testDrives', 'purchaseRequests', 'promos'];

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

    protected function conditionLabel(): Attribute
    {
        return Attribute::get(fn () => self::CONDITIONS[$this->vehicle_condition] ?? $this->vehicle_condition);
    }

    protected function transmissionLabel(): Attribute
    {
        return Attribute::get(fn () => self::TRANSMISSIONS[$this->transmission] ?? $this->transmission);
    }

    protected function fuelTypeLabel(): Attribute
    {
        return Attribute::get(fn () => self::FUEL_TYPES[$this->fuel_type] ?? $this->fuel_type);
    }

    /**
     * Ringkasan relasi penghalang hapus, mis. "2 test drive dan 1 pengajuan"; null jika boleh dihapus.
     * Memerlukan withCount/loadCount(self::DELETION_BLOCKERS).
     */
    public function deletionBlockers(): ?string
    {
        $parts = array_values(array_filter([
            $this->test_drives_count ? "{$this->test_drives_count} test drive" : null,
            $this->purchase_requests_count ? "{$this->purchase_requests_count} pengajuan" : null,
            $this->promos_count ? "{$this->promos_count} promo" : null,
        ]));

        if ($parts === []) {
            return null;
        }

        $last = array_pop($parts);

        return $parts === [] ? $last : implode(', ', $parts).' dan '.$last;
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
