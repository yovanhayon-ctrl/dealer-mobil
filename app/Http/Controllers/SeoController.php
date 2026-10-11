<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Promo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

/**
 * File untuk mesin pencari: /sitemap.xml dan /robots.txt.
 * Semua URL dibentuk dari APP_URL, jadi otomatis benar saat dipasang di domain lain.
 */
class SeoController extends Controller
{
    /** Halaman publik tetap (nama route). */
    public const STATIC_ROUTES = ['home', 'cars.index', 'promos.index', 'services.index', 'credit.index', 'about', 'contact'];

    /** Bagian yang tidak perlu diindeks (akun, admin, form login/daftar). */
    public const DISALLOW = ['/admin', '/akun', '/login', '/register', '/lupa-kata-sandi', '/reset-kata-sandi', '/bandingkan'];

    public function sitemap(): Response
    {
        $cars = Car::query()->active()->select(['id', 'slug', 'updated_at'])->orderBy('id')->get();

        // Sama dengan halaman promo publik: promo berjalan, umum atau mobilnya masih aktif.
        $promos = Promo::query()->active()
            ->where(fn (Builder $query) => $query
                ->whereNull('car_id')
                ->orWhereHas('car', fn (Builder $car) => $car->active()))
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->get();

        return response()
            ->view('seo.sitemap', [
                'staticUrls' => array_map(fn (string $name) => route($name), self::STATIC_ROUTES),
                'cars' => $cars,
                'promos' => $promos,
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        foreach (self::DISALLOW as $path) {
            $lines[] = "Disallow: {$path}";
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
