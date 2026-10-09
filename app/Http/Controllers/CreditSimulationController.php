<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Support\CreditCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

/**
 * Simulasi kredit publik (/simulasi-kredit): perhitungan bunga flat tanpa tabel (RANCANGAN §5).
 *
 * Form memakai GET agar hasil bisa dibagikan dan tetap berfungsi tanpa JavaScript; JavaScript
 * (public/js/credit-simulation.js) menghitung ulang secara live dengan rumus integer yang sama.
 * Input tidak valid ditampilkan sebagai pesan di form (bukan redirect).
 */
class CreditSimulationController extends Controller
{
    public const DEFAULT_TENOR = 36;

    public const MIN_PRICE = 1_000_000;

    /** Batas harga manual; juga menjaga perhitungan JavaScript tetap di bawah Number.MAX_SAFE_INTEGER. */
    public const MAX_PRICE = 10_000_000_000;

    public function index(Request $request, CreditCalculator $credit): View
    {
        $cars = $this->cars();
        $slug = $this->text($request, 'mobil');
        $car = $slug !== null ? $cars->firstWhere('slug', $slug) : null;
        $errors = new MessageBag;

        $price = $car?->finalPrice() ?? $this->digits($request, 'harga');
        $tenor = (int) $request->query('tenor');
        $tenor = in_array($tenor, $credit->tenors(), true) ? $tenor : self::DEFAULT_TENOR;
        $downPayment = $this->digits($request, 'dp');

        if ($car === null && $price !== null && ($price < self::MIN_PRICE || $price > self::MAX_PRICE)) {
            $errors->add('harga', 'Harga harus antara '.self::rupiah(self::MIN_PRICE).' dan '.self::rupiah(self::MAX_PRICE).'.');
        }

        $result = null;
        $comparison = [];

        if ($price !== null && $errors->isEmpty()) {
            $minDownPayment = $credit->minDownPayment($price);
            $maxDownPayment = $credit->maxDownPayment($price);
            $downPayment ??= $minDownPayment;

            if ($downPayment < $minDownPayment || $downPayment > $maxDownPayment) {
                $errors->add('dp', sprintf(
                    'Uang muka harus antara %s (%d%%) dan %s (%d%%) dari harga.',
                    self::rupiah($minDownPayment), config('credit.dp_min'),
                    self::rupiah($maxDownPayment), config('credit.dp_max'),
                ));
            } else {
                $result = $credit->calculate($price, $downPayment, $tenor);
                $comparison = array_map(fn (int $months) => $credit->calculate($price, $downPayment, $months), $credit->tenors());
            }
        }

        // Dibagikan global (bukan withErrors) agar komponen x-form.* ikut membaca pesannya.
        ViewFactory::share('errors', (new ViewErrorBag)->put('default', $errors));

        return view('pages.credit.index', [
            'cars' => $cars,
            'car' => $car,
            'unknownCar' => $slug !== null && $car === null,
            'price' => $price,
            'downPayment' => $downPayment,
            'tenor' => $tenor,
            'result' => $result,
            'comparison' => $comparison,
            'tenors' => $credit->tenors(),
            'rates' => config('credit.rates'),
        ]);
    }

    public static function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    /**
     * Mobil aktif untuk dropdown, label "Merek Nama Tahun", harga setelah promo aktif.
     *
     * @return Collection<int, Car>
     */
    private function cars(): Collection
    {
        return Car::query()
            ->active()
            ->with(['brand:id,name', 'activePromos:id,car_id,discount_amount'])
            ->get(['id', 'brand_id', 'name', 'slug', 'year', 'price', 'vehicle_condition', 'stock', 'is_active'])
            ->sortBy(fn (Car $car) => mb_strtolower("{$car->brand->name} {$car->name} {$car->year}"))
            ->values();
    }

    private function text(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * Angka boleh memakai titik ribuan ("60.000.000"); maksimal 13 digit agar tidak overflow.
     */
    private function digits(Request $request, string $key): ?int
    {
        $digits = ltrim((string) preg_replace('/\D/', '', (string) $this->text($request, $key)), '0');

        if ($digits === '') {
            return $this->text($request, $key) !== null ? 0 : null;
        }

        return strlen($digits) <= 13 ? (int) $digits : PHP_INT_MAX;
    }
}
