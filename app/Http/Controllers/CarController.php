<?php

namespace App\Http\Controllers;

use App\Catalog\CarCatalogFilters;
use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Support\CreditCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Katalog & detail mobil publik (/mobil, /mobil/{slug}). Pengelolaan mobil ada di Admin\CarController.
 */
class CarController extends Controller
{
    public const PER_PAGE = 12;

    public const SIMILAR_CARS = 4;

    public function index(Request $request): View
    {
        // Hanya merek & kategori yang punya mobil aktif (dropdown filter + pencocokan slug).
        $hasActiveCars = fn (Builder $query) => $query->whereHas('cars', fn (Builder $cars) => $cars->active());
        $brands = Brand::query()->tap($hasActiveCars)->orderBy('name')->get(['id', 'name', 'slug']);
        $categories = Category::query()->tap($hasActiveCars)->orderBy('name')->get(['id', 'name', 'slug']);

        $filters = CarCatalogFilters::fromRequest($request, $brands, $categories);

        $cars = Car::query()
            ->active()
            ->withFinalPrice()
            ->with(Car::CARD_RELATIONS)
            ->tap(fn (Builder $query) => $filters->apply($query))
            ->paginate(self::PER_PAGE)
            ->appends($filters->query());

        return view('pages.cars.index', [
            'cars' => $cars,
            'filters' => $filters,
            'brands' => $brands,
            'categories' => $categories,
        ]);
    }

    /**
     * Detail mobil. Jumlah query tetap: mobil, 4 relasi, mobil serupa + relasi kartunya.
     */
    public function show(Car $car, CreditCalculator $credit): View
    {
        abort_unless($car->is_active, 404);

        $car->load([
            'brand:id,name,slug',
            'category:id,name,slug',
            // Gambar utama dulu, lalu sesuai urutan galeri admin.
            'images' => fn ($query) => $query->reorder()->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
            'activePromos' => fn ($query) => $query->orderByDesc('discount_amount')->orderBy('id'),
        ]);

        $title = "{$car->brand->name} {$car->name} {$car->year}";
        $finalPrice = $car->finalPrice();

        return view('pages.cars.show', [
            'car' => $car,
            'title' => $title,
            'finalPrice' => $finalPrice,
            'bestPromo' => $car->bestActivePromo(),
            'minDownPayment' => $credit->minDownPayment($finalPrice),
            'installments' => collect($credit->tenors())
                ->map(fn (int $tenor) => $credit->calculate($finalPrice, $credit->minDownPayment($finalPrice), $tenor))
                ->all(),
            'similarCars' => $this->similarCars($car),
            'whatsappUrl' => $this->whatsappUrl($car, $title, $finalPrice),
        ]);
    }

    /**
     * Mobil aktif lain dari kategori yang sama, dilengkapi merek yang sama bila kurang (satu query).
     *
     * @return Collection<int, Car>
     */
    private function similarCars(Car $car): Collection
    {
        return Car::query()
            ->active()
            ->with(Car::CARD_RELATIONS)
            ->whereKeyNot($car->id)
            ->where(fn (Builder $query) => $query
                ->where('category_id', $car->category_id)
                ->orWhere('brand_id', $car->brand_id))
            ->orderByRaw('CASE WHEN category_id = ? THEN 0 ELSE 1 END', [$car->category_id])
            ->latest()
            ->orderByDesc('id')
            ->limit(self::SIMILAR_CARS)
            ->get();
    }

    /**
     * Link WhatsApp dengan pesan berisi nama mobil, harga, dan URL halaman; null jika nomor dealer kosong.
     */
    private function whatsappUrl(Car $car, string $title, int $price): ?string
    {
        $number = preg_replace('/\D/', '', (string) config('dealer.whatsapp'));

        if ($number === '') {
            return null;
        }

        $message = "Halo, saya tertarik dengan {$title} (Rp ".number_format($price, 0, ',', '.').'). '
            .route('cars.show', $car);

        return "https://wa.me/{$number}?text=".rawurlencode($message);
    }
}
