<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\Promo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public const LATEST_CARS = 8;

    public const RUNNING_PROMOS = 3;

    /**
     * Beranda. Jumlah query tetap (tidak bertambah seiring jumlah data).
     */
    public function __invoke(): View
    {
        $activeCars = fn (Builder $query) => $query->active();

        return view('pages.home', [
            'latestCars' => Car::query()
                ->active()
                ->with(Car::CARD_RELATIONS)
                ->latest()
                ->orderByDesc('id')
                ->limit(self::LATEST_CARS)
                ->get(),
            // Promo umum, atau promo khusus mobil yang masih aktif di katalog.
            'promos' => Promo::query()
                ->active()
                ->where(fn (Builder $query) => $query->whereNull('car_id')->orWhereHas('car', $activeCars))
                ->with('car.brand')
                ->latest()
                ->orderByDesc('id')
                ->limit(self::RUNNING_PROMOS)
                ->get(),
            'brands' => Brand::query()
                ->select(['id', 'name', 'slug'])
                ->whereHas('cars', $activeCars)
                ->withCount(['cars' => $activeCars])
                ->orderBy('name')
                ->get(),
            'categories' => Category::query()
                ->select(['id', 'name', 'slug'])
                ->whereHas('cars', $activeCars)
                ->withCount(['cars' => $activeCars])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
