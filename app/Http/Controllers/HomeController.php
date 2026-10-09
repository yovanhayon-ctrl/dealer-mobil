<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\Promo;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public const LATEST_CARS = 8;

    public const RUNNING_PROMOS = 3;

    public const TESTIMONIALS = 3;

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
            // Ulasan yang disetujui admin + ringkasan rating (1 query agregat).
            'testimonials' => Testimonial::query()
                ->approved()
                ->with([
                    'user:id,name',
                    'purchaseRequest:id,car_id',
                    'purchaseRequest.car:id,brand_id,name,year',
                    'purchaseRequest.car.brand:id,name',
                ])
                ->latest('approved_at')
                ->orderByDesc('id')
                ->limit(self::TESTIMONIALS)
                ->get(),
            'ratingSummary' => Testimonial::query()
                ->approved()
                ->selectRaw('COUNT(*) AS total, AVG(rating) AS average')
                ->toBase()
                ->first(),
        ]);
    }
}
