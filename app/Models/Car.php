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
use InvalidArgumentException;

#[Fillable([
    'brand_id', 'category_id', 'name', 'slug', 'vehicle_condition', 'year', 'mileage',
    'price', 'transmission', 'fuel_type', 'engine_cc', 'seats', 'color', 'stock',
    'description', 'is_active', 'is_featured',
])]
class Car extends Model
{
    /** @use HasFactory<CarFactory> */
    use HasFactory, HasUniqueSlug;

    /** Tahun produksi terlama yang boleh diinput (koleksi heritage & klasik Jepang, mis. AE86 1986). */
    public const MIN_YEAR = 1950;

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

    /** Batas "stok menipis" di laporan admin (mobil aktif dengan stok ≤ nilai ini). */
    public const LOW_STOCK_THRESHOLD = 1;

    /** Relasi yang dipakai x-car-card; di-eager load agar tidak terjadi N+1. */
    public const CARD_RELATIONS = [
        'brand:id,name,slug', 'category:id,name,slug', 'primaryImage:id,car_id,path',
        'activePromos:id,car_id,discount_amount',
    ];

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
            'is_featured' => 'boolean',
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

    /**
     * Mobil yang boleh di-test drive: aktif, dan baru (stok 0 tetap boleh) atau bekas dengan stok.
     * Aturannya sama dengan canBeTestDriven().
     */
    #[Scope]
    protected function testDrivable(Builder $query): void
    {
        $query->where('cars.is_active', true)
            ->where(fn (Builder $query) => $query
                ->where('cars.vehicle_condition', self::CONDITION_NEW)
                ->orWhere('cars.stock', '>', 0));
    }

    /**
     * Tambah kolom `final_price` (harga setelah diskon promo aktif terbesar) yang dihitung di SQL,
     * untuk urutan harga di katalog. Aturannya sama dengan bestActivePromo().
     */
    #[Scope]
    protected function withFinalPrice(Builder $query): void
    {
        if ($query->getQuery()->columns === null) {
            $query->select('cars.*');
        }

        [$sql, $bindings] = self::finalPriceSql();
        $query->selectRaw("{$sql} as final_price", $bindings);
    }

    /**
     * Filter menurut harga setelah promo, mis. whereFinalPrice('>=', 100_000_000).
     */
    #[Scope]
    protected function whereFinalPrice(Builder $query, string $operator, int $amount): void
    {
        if (! in_array($operator, ['<', '<=', '=', '>=', '>'], true)) {
            throw new InvalidArgumentException("Operator {$operator} tidak didukung.");
        }

        [$sql, $bindings] = self::finalPriceSql();
        $query->whereRaw("{$sql} {$operator} ?", [...$bindings, $amount]);
    }

    /**
     * Ekspresi SQL harga setelah promo: harga − diskon promo aktif terbesar (diskon ≥ harga diabaikan).
     *
     * @return array{0: string, 1: array<int, mixed>}
     */
    private static function finalPriceSql(): array
    {
        $discount = Promo::query()
            ->selectRaw('MAX(promos.discount_amount)')
            ->active()
            ->whereColumn('promos.car_id', 'cars.id')
            ->where('promos.discount_amount', '>', 0)
            ->whereColumn('promos.discount_amount', '<', 'cars.price');

        return ['(cars.price - COALESCE(('.$discount->toSql().'), 0))', $discount->getBindings()];
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

    /**
     * Boleh diajukan pembelian: mobil aktif dan stok tersedia (RANCANGAN §5).
     */
    public function canBePurchased(): bool
    {
        return $this->is_active && $this->inStock();
    }

    /**
     * Boleh test drive: mobil baru stok 0 tetap boleh (unit display), mobil bekas stok 0 tidak (sudah terjual).
     */
    public function canBeTestDriven(): bool
    {
        return $this->is_active && ($this->isNew() || $this->inStock());
    }

    /**
     * Promo aktif dengan diskon terbesar. Memerlukan eager load `activePromos`
     * (preventLazyLoading melempar exception bila lupa, supaya tidak terjadi N+1).
     * Diskon yang tidak lebih kecil dari harga saat ini diabaikan (mis. harga mobil diturunkan setelah promo dibuat).
     */
    public function bestActivePromo(): ?Promo
    {
        return $this->activePromos
            ->filter(fn (Promo $promo) => $promo->discount_amount > 0 && $promo->discount_amount < $this->price)
            ->sortByDesc('discount_amount')
            ->first();
    }

    public function promoDiscount(): int
    {
        return $this->bestActivePromo()?->discount_amount ?? 0;
    }

    /**
     * Harga setelah diskon promo aktif terbesar (dipakai katalog & pengajuan).
     */
    public function finalPrice(): int
    {
        return $this->price - $this->promoDiscount();
    }

    public function hasPromoPrice(): bool
    {
        return $this->promoDiscount() > 0;
    }
}
