<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Support\CarComparison;
use App\Support\CreditCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Bandingkan 2–3 mobil berdampingan. Pilihan disimpan di session, jadi tamu juga bisa memakainya.
 */
class CompareController extends Controller
{
    public function index(CarComparison $comparison, CreditCalculator $credit): View
    {
        $cars = $comparison->cars();

        $rows = $cars->map(function (Car $car) use ($credit) {
            $finalPrice = $car->finalPrice();

            return [
                'car' => $car,
                'final_price' => $finalPrice,
                // Cicilan termurah: DP minimum + tenor terpanjang (sama dengan kartu mobil).
                'installment' => $credit->calculate($finalPrice, $credit->minDownPayment($finalPrice), max($credit->tenors()))['monthly_installment'],
            ];
        });

        return view('pages.compare.index', [
            'rows' => $rows,
            'best' => $this->bestValues($rows->all()),
        ]);
    }

    public function store(Car $car, CarComparison $comparison): RedirectResponse
    {
        abort_unless($car->is_active, 404);

        $title = "{$car->name} {$car->year}";
        $redirect = redirect()->back(fallback: route('compare.index'));

        return match ($comparison->add($car)) {
            'exists' => $redirect->with('status', "{$title} sudah ada di daftar perbandingan."),
            'full' => $redirect->with('error', 'Maksimal '.CarComparison::MAX_CARS.' mobil dibandingkan. Hapus salah satu terlebih dahulu.'),
            default => $redirect->with('success', "{$title} ditambahkan ke perbandingan."),
        };
    }

    public function destroy(Car $car, CarComparison $comparison): RedirectResponse
    {
        $comparison->remove($car);

        return redirect()->back(fallback: route('compare.index'))
            ->with('success', "{$car->name} {$car->year} dihapus dari perbandingan.");
    }

    public function clear(CarComparison $comparison): RedirectResponse
    {
        $comparison->clear();

        return redirect()->back(fallback: route('cars.index'))
            ->with('success', 'Daftar perbandingan dikosongkan.');
    }

    /**
     * Nilai terbaik per baris (harga termurah, tahun termuda, km terendah) untuk badge "Terbaik".
     * Hanya bila ada ≥ 2 pembanding dan nilainya tidak sama semua.
     *
     * @param  list<array{car: Car, final_price: int, installment: int}>  $rows
     * @return array{price: ?int, year: ?int, mileage: ?int}
     */
    private function bestValues(array $rows): array
    {
        $pick = function (array $values, callable $choose): ?int {
            return count($values) >= 2 && count(array_unique($values)) > 1 ? $choose($values) : null;
        };
        $usedCars = array_filter($rows, fn (array $row) => ! $row['car']->isNew());

        return [
            'price' => $pick(array_column($rows, 'final_price'), 'min'),
            'year' => $pick(array_map(fn (array $row) => $row['car']->year, $rows), 'max'),
            'mileage' => $pick(array_map(fn (array $row) => $row['car']->mileage, $usedCars), 'min'),
        ];
    }
}
