<?php

namespace App\Catalog;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Filter & urutan katalog publik /mobil. Nilai query string yang tidak valid diabaikan
 * (bukan redirect/422), sama seperti filter daftar mobil admin.
 */
final class CarCatalogFilters
{
    /** Pilihan urutan: nilai query string => [label, kolom, arah]. */
    public const SORTS = [
        'terbaru' => ['Terbaru', 'cars.created_at', 'desc'],
        'harga_termurah' => ['Harga termurah', 'final_price', 'asc'],
        'harga_termahal' => ['Harga termahal', 'final_price', 'desc'],
        'tahun_terbaru' => ['Tahun terbaru', 'cars.year', 'desc'],
        'tahun_terlama' => ['Tahun terlama', 'cars.year', 'asc'],
        'km_terendah' => ['Kilometer terendah', 'cars.mileage', 'asc'],
    ];

    public const DEFAULT_SORT = 'terbaru';

    /** Pilihan jumlah kursi di form filter (query string menerima 2–20). */
    public const SEAT_OPTIONS = [2, 4, 5, 6, 7, 8];

    public const MIN_SEATS = 2;

    public const MAX_SEATS = 20;

    public const MIN_YEAR = 1900;

    public const MAX_KEYWORD_LENGTH = 100;

    public const MAX_PRICE = 1_000_000_000_000;

    private function __construct(
        public readonly ?string $keyword,
        public readonly ?Brand $brand,
        public readonly ?Category $category,
        public readonly ?string $condition,
        public readonly ?string $transmission,
        public readonly ?string $fuelType,
        public readonly ?string $color,
        public readonly ?int $minPrice,
        public readonly ?int $maxPrice,
        public readonly ?int $minYear,
        public readonly ?int $maxYear,
        public readonly ?int $seats,
        public readonly bool $promoOnly,
        public readonly string $sort,
    ) {}

    /**
     * @param  Collection<int, Brand>  $brands  merek yang boleh dipilih (dicocokkan lewat slug)
     * @param  Collection<int, Category>  $categories  kategori yang boleh dipilih (dicocokkan lewat slug)
     * @param  Collection<int, string>  $colors  warna yang boleh dipilih (tanpa beda huruf besar/kecil)
     */
    public static function fromRequest(Request $request, Collection $brands, Collection $categories, ?Collection $colors = null): self
    {
        // Nilai array (mis. ?merek[]=x) dan string kosong dianggap tidak diisi.
        $text = function (string $key) use ($request): ?string {
            $value = $request->query($key);

            return is_string($value) && trim($value) !== '' ? trim($value) : null;
        };
        $pick = fn (string $key, array $allowed) => in_array($text($key), $allowed, true) ? $text($key) : null;
        $slug = fn (string $key, Collection $models) => preg_match('/^[a-z0-9-]+$/', (string) $text($key))
            ? $models->firstWhere('slug', $text($key))
            : null;
        $integer = function (string $key, int $min, int $max) use ($text): ?int {
            $value = (string) $text($key);

            return ctype_digit($value) && strlen($value) <= 6 && (int) $value >= $min && (int) $value <= $max
                ? (int) $value
                : null;
        };
        // Harga boleh memakai titik ribuan ("285.000.000"): ambil digitnya saja (seperti NormalizesDigits).
        // 0 dan harga di atas MAX_PRICE diabaikan (panjang digit dicek dulu agar tidak overflow).
        $price = function (string $key) use ($text): ?int {
            $digits = ltrim((string) preg_replace('/\D/', '', (string) $text($key)), '0');

            return $digits !== '' && strlen($digits) <= 13 && (int) $digits <= self::MAX_PRICE ? (int) $digits : null;
        };

        // Warna dicocokkan dengan daftar warna mobil aktif; nilai yang tersimpan yang dipakai.
        $color = $text('warna') === null ? null : ($colors ?? collect())
            ->first(fn (string $value) => mb_strtolower($value) === mb_strtolower($text('warna')));

        $maxYear = (int) now()->year + 1;
        [$minPrice, $maxPrice] = self::ordered($price('harga_min'), $price('harga_max'));
        [$minYear, $maxYear] = self::ordered(
            $integer('tahun_min', self::MIN_YEAR, $maxYear),
            $integer('tahun_max', self::MIN_YEAR, $maxYear),
        );

        return new self(
            keyword: trim(mb_substr((string) $text('q'), 0, self::MAX_KEYWORD_LENGTH)) ?: null,
            brand: $slug('merek', $brands),
            category: $slug('kategori', $categories),
            condition: $pick('kondisi', array_keys(Car::CONDITIONS)),
            transmission: $pick('transmisi', array_keys(Car::TRANSMISSIONS)),
            fuelType: $pick('bbm', array_keys(Car::FUEL_TYPES)),
            color: $color,
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            minYear: $minYear,
            maxYear: $maxYear,
            seats: $integer('kursi', self::MIN_SEATS, self::MAX_SEATS),
            promoOnly: $text('promo') === '1',
            sort: $pick('urut', array_keys(self::SORTS)) ?? self::DEFAULT_SORT,
        );
    }

    /**
     * Terapkan filter + urutan. Urutan harga memerlukan scope Car::withFinalPrice().
     */
    public function apply(Builder $query): void
    {
        [, $sortColumn, $sortDirection] = self::SORTS[$this->sort];

        $query
            ->when($this->keyword, fn (Builder $query, string $keyword) => $query->where(fn (Builder $query) => $query
                ->where('cars.name', 'like', "%{$keyword}%")
                ->orWhereHas('brand', fn (Builder $query) => $query->where('name', 'like', "%{$keyword}%"))))
            ->when($this->brand, fn (Builder $query, Brand $brand) => $query->where('cars.brand_id', $brand->id))
            ->when($this->category, fn (Builder $query, Category $category) => $query->where('cars.category_id', $category->id))
            ->when($this->condition, fn (Builder $query, string $condition) => $query->where('cars.vehicle_condition', $condition))
            ->when($this->transmission, fn (Builder $query, string $transmission) => $query->where('cars.transmission', $transmission))
            ->when($this->fuelType, fn (Builder $query, string $fuelType) => $query->where('cars.fuel_type', $fuelType))
            ->when($this->color, fn (Builder $query, string $color) => $query->where('cars.color', $color))
            ->when($this->minPrice, fn (Builder $query, int $price) => $query->whereFinalPrice('>=', $price))
            ->when($this->maxPrice, fn (Builder $query, int $price) => $query->whereFinalPrice('<=', $price))
            ->when($this->minYear, fn (Builder $query, int $year) => $query->where('cars.year', '>=', $year))
            ->when($this->maxYear, fn (Builder $query, int $year) => $query->where('cars.year', '<=', $year))
            ->when($this->seats, fn (Builder $query, int $seats) => $query->where('cars.seats', $seats))
            // Aturan sama dengan Car::bestActivePromo(): diskon > 0 dan < harga mobil.
            ->when($this->promoOnly, fn (Builder $query) => $query->whereHas('activePromos', fn (Builder $query) => $query
                ->where('promos.discount_amount', '>', 0)
                ->whereColumn('promos.discount_amount', '<', 'cars.price')))
            ->orderBy($sortColumn, $sortDirection)
            ->orderByDesc('cars.id');
    }

    /**
     * Query string bersih (hanya filter yang valid), dipakai link pagination, chip, dan form urutan.
     *
     * @return array<string, string|int>
     */
    public function query(): array
    {
        return array_filter([
            'q' => $this->keyword,
            'merek' => $this->brand?->slug,
            'kategori' => $this->category?->slug,
            'kondisi' => $this->condition,
            'transmisi' => $this->transmission,
            'bbm' => $this->fuelType,
            'warna' => $this->color,
            'harga_min' => $this->minPrice,
            'harga_max' => $this->maxPrice,
            'tahun_min' => $this->minYear,
            'tahun_max' => $this->maxYear,
            'kursi' => $this->seats,
            'promo' => $this->promoOnly ? 1 : null,
            'urut' => $this->sort === self::DEFAULT_SORT ? null : $this->sort,
        ], fn ($value) => $value !== null);
    }

    public function hasAny(): bool
    {
        return Arr::except($this->query(), 'urut') !== [];
    }

    /**
     * Chip filter aktif: label + URL katalog tanpa filter tersebut.
     *
     * @return list<array{label: string, url: string}>
     */
    public function activeChips(): array
    {
        $rupiah = fn (int $amount) => 'Rp '.number_format($amount, 0, ',', '.');

        $chips = [
            [$this->keyword !== null ? "“{$this->keyword}”" : null, ['q']],
            [$this->brand?->name, ['merek']],
            [$this->category?->name, ['kategori']],
            [Car::CONDITIONS[$this->condition] ?? null, ['kondisi']],
            [Car::TRANSMISSIONS[$this->transmission] ?? null, ['transmisi']],
            [Car::FUEL_TYPES[$this->fuelType] ?? null, ['bbm']],
            [$this->color !== null ? "Warna {$this->color}" : null, ['warna']],
            [self::rangeLabel('Harga', $this->minPrice, $this->maxPrice, $rupiah), ['harga_min', 'harga_max']],
            [self::rangeLabel('Tahun', $this->minYear, $this->maxYear, fn (int $year) => (string) $year), ['tahun_min', 'tahun_max']],
            [$this->seats !== null ? "{$this->seats} kursi" : null, ['kursi']],
            [$this->promoOnly ? 'Hanya promo' : null, ['promo']],
        ];

        return collect($chips)
            ->filter(fn (array $chip) => $chip[0] !== null)
            ->map(fn (array $chip) => [
                'label' => $chip[0],
                'url' => route('cars.index', Arr::except($this->query(), $chip[1])),
            ])
            ->values()
            ->all();
    }

    /**
     * Judul halaman katalog, mis. "Mobil", "Mobil Toyota", "Mobil Bekas Toyota SUV".
     */
    public function title(): string
    {
        return implode(' ', array_filter([
            'Mobil',
            Car::CONDITIONS[$this->condition] ?? null,
            $this->brand?->name,
            $this->category?->name,
        ]));
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    private static function ordered(?int $min, ?int $max): array
    {
        return $min !== null && $max !== null && $min > $max ? [$max, $min] : [$min, $max];
    }

    private static function rangeLabel(string $label, ?int $min, ?int $max, callable $format): ?string
    {
        return match (true) {
            $min !== null && $max !== null => "{$label} {$format($min)} - {$format($max)}",
            $min !== null => "{$label} mulai {$format($min)}",
            $max !== null => "{$label} s/d {$format($max)}",
            default => null,
        };
    }
}
