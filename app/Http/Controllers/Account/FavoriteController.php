<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mobil favorit customer: simpan / hapus dari kartu & detail mobil, lihat di "Favorit Saya".
 */
class FavoriteController extends Controller
{
    public const PER_PAGE = 12;

    public const MAX_FAVORITES = 50;

    public function index(Request $request): View
    {
        $this->ensureCustomer($request);

        $cars = $request->user()->favoriteCars()
            ->with(Car::CARD_RELATIONS)
            ->paginate(self::PER_PAGE);

        return view('pages.account.favorites.index', ['cars' => $cars]);
    }

    public function store(Request $request, Car $car): RedirectResponse
    {
        $this->ensureCustomer($request);
        // Mobil nonaktif tidak tampil publik.
        abort_unless($car->is_active, 404);

        $user = $request->user();
        $redirect = redirect()->back(fallback: route('cars.show', $car));
        $title = "{$car->name} {$car->year}";

        if ($user->favoriteCars()->whereKey($car->id)->exists()) {
            return $redirect->with('status', "{$title} sudah ada di favorit Anda.");
        }

        if ($user->favoriteCars()->count() >= self::MAX_FAVORITES) {
            return $redirect->with('error', 'Favorit sudah penuh (maks. '.self::MAX_FAVORITES.' mobil). Hapus salah satu terlebih dahulu.');
        }

        // syncWithoutDetaching: klik ganda / dua tab tidak membuat data ganda.
        $user->favoriteCars()->syncWithoutDetaching([$car->id]);
        $user->forgetFavoriteCarIds();

        return $redirect->with('success', "{$title} disimpan ke favorit.");
    }

    public function destroy(Request $request, Car $car): RedirectResponse
    {
        $this->ensureCustomer($request);

        $request->user()->favoriteCars()->detach($car->id);
        $request->user()->forgetFavoriteCarIds();

        return redirect()->back(fallback: route('account.favorites.index'))
            ->with('success', "{$car->name} {$car->year} dihapus dari favorit.");
    }

    /**
     * Favorit hanya untuk akun customer (sama seperti booking & pengajuan).
     */
    private function ensureCustomer(Request $request): void
    {
        abort_if($request->user()->isAdmin(), 403);
    }
}
