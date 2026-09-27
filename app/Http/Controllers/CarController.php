<?php

namespace App\Http\Controllers;

use App\Catalog\CarCatalogFilters;
use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Katalog mobil publik (/mobil). Pengelolaan mobil ada di Admin\CarController.
 */
class CarController extends Controller
{
    public const PER_PAGE = 12;

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
}
