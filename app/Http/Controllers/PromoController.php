<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Promo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Promo publik (/promo, /promo/{slug}). Pengelolaan promo ada di Admin\PromoController.
 *
 * Yang tampil: promo umum, atau promo khusus mobil yang mobilnya masih aktif (sama dengan beranda).
 * Detail promo nonaktif/terjadwal → 404; promo yang sudah berakhir tetap bisa dibuka dengan pemberitahuan.
 */
class PromoController extends Controller
{
    public const PER_PAGE = 9;

    public const OTHER_PROMOS = 3;

    /** Filter jenis: nilai query string => label. */
    public const TYPE_FILTERS = [
        'mobil' => 'Khusus Mobil',
        'umum' => 'Promo Umum',
    ];

    public function index(Request $request): View
    {
        $type = array_key_exists((string) $request->query('jenis'), self::TYPE_FILTERS) ? $request->query('jenis') : null;

        $promos = $this->visible()
            ->active()
            ->when($type === 'mobil', fn (Builder $query) => $query->whereNotNull('car_id'))
            ->when($type === 'umum', fn (Builder $query) => $query->whereNull('car_id'))
            ->with('car.brand')
            // Yang paling cepat berakhir tampil duluan.
            ->orderBy('end_date')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('pages.promos.index', ['promos' => $promos, 'type' => $type]);
    }

    public function show(Promo $promo): View
    {
        // Belum dirilis (nonaktif / terjadwal) tidak boleh terlihat publik.
        abort_if(in_array($promo->status, [Promo::STATUS_INACTIVE, Promo::STATUS_SCHEDULED], true), 404);

        $promo->load(['car' => fn ($query) => $query->with(Car::CARD_RELATIONS)]);
        abort_if($promo->car_id !== null && ! $promo->car?->is_active, 404);

        return view('pages.promos.show', [
            'promo' => $promo,
            'isEnded' => $promo->status === Promo::STATUS_ENDED,
            'otherPromos' => $this->visible()
                ->active()
                ->whereKeyNot($promo->id)
                ->with('car.brand')
                ->orderBy('end_date')
                ->orderByDesc('id')
                ->limit(self::OTHER_PROMOS)
                ->get(),
            'whatsappUrl' => $this->whatsappUrl($promo),
        ]);
    }

    /**
     * Promo umum, atau promo khusus mobil yang mobilnya aktif di katalog.
     */
    private function visible(): Builder
    {
        return Promo::query()->where(fn (Builder $query) => $query
            ->whereNull('car_id')
            ->orWhereHas('car', fn (Builder $car) => $car->active()));
    }

    /**
     * Link WhatsApp berisi judul & URL promo; null jika nomor dealer kosong.
     */
    private function whatsappUrl(Promo $promo): ?string
    {
        $number = preg_replace('/\D/', '', (string) config('dealer.whatsapp'));

        if ($number === '') {
            return null;
        }

        $message = "Halo, saya ingin bertanya tentang promo \"{$promo->title}\". ".route('promos.show', $promo);

        return "https://wa.me/{$number}?text=".rawurlencode($message);
    }
}
