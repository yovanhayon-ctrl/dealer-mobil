<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Halaman informasi statis: Tentang Kami, Kontak, Kebijakan Privasi, dan Syarat & Ketentuan.
 * Data dealer dibaca dari config/dealer.php (.env).
 */
class PageController extends Controller
{
    /** Tanggal berlaku Kebijakan Privasi & Syarat Ketentuan; ubah bila isinya diubah. */
    public const LEGAL_EFFECTIVE_DATE = '2026-10-17';

    public function about(): View
    {
        return view('pages.about', [
            'stats' => [
                'cars' => Car::query()->active()->count(),
                'brands' => Brand::query()->whereHas('cars', fn (Builder $cars) => $cars->active())->count(),
                'services' => Service::query()->active()->count(),
            ],
        ]);
    }

    public function contact(): View
    {
        $dealer = config('dealer');

        return view('pages.contact', [
            'dealer' => $dealer,
            'phoneUrl' => self::phoneUrl($dealer['phone']),
            'whatsappUrl' => ($number = preg_replace('/\D/', '', (string) $dealer['whatsapp'])) !== ''
                ? "https://wa.me/{$number}?text=".rawurlencode('Halo '.$dealer['name'].', saya ingin bertanya.')
                : null,
            'mapsUrl' => self::mapsEmbedUrl($dealer['maps_embed_url']),
        ]);
    }

    public function privacy(): View
    {
        return view('pages.legal.privacy', $this->legalData());
    }

    public function terms(): View
    {
        return view('pages.legal.terms', $this->legalData());
    }

    /**
     * Kontak dealer (dari .env) & tanggal berlaku untuk halaman kebijakan.
     *
     * @return array<string, mixed>
     */
    private function legalData(): array
    {
        $dealer = config('dealer');
        $whatsapp = preg_replace('/\D/', '', (string) $dealer['whatsapp']);

        return [
            'dealer' => $dealer,
            'whatsappUrl' => $whatsapp !== '' ? "https://wa.me/{$whatsapp}" : null,
            'effectiveDate' => Carbon::parse(self::LEGAL_EFFECTIVE_DATE)->translatedFormat('j F Y'),
        ];
    }

    /**
     * Link tel: dari nomor telepon dealer ("021-5550-8888" → "tel:02155508888"); null bila kosong.
     */
    public static function phoneUrl(?string $phone): ?string
    {
        $digits = preg_replace('/[^\d+]/', '', (string) $phone);

        return $digits !== '' ? "tel:{$digits}" : null;
    }

    /**
     * Hanya URL embed Google Maps (https) yang boleh dipasang sebagai iframe:
     * format "Sematkan peta" (/maps/embed?pb=…) atau format lama (/maps?q=…&output=embed).
     */
    public static function mapsEmbedUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        $isGoogleMaps = preg_match('#^https://(www\.|maps\.)?google\.com/maps(/embed)?\?#', $url) === 1;
        $isEmbed = str_contains($url, '/maps/embed?') || str_contains($url, 'output=embed');

        return $isGoogleMaps && $isEmbed ? $url : null;
    }
}
