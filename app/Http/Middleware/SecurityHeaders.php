<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan untuk semua response web (Phase 16).
 *
 * CSP hanya mengizinkan sumber yang memang dipakai: aset lokal, jsDelivr (Bootstrap & ikon),
 * Google Fonts, dan iframe Google Maps (halaman Kontak). Tidak ada script inline di view;
 * style inline (atribut style="…", mis. lebar progress bar) masih dipakai, jadi style-src
 * mengizinkan 'unsafe-inline'. img-src blob: untuk pratinjau upload di admin (URL.createObjectURL).
 */
class SecurityHeaders
{
    public const CONTENT_SECURITY_POLICY = [
        "default-src 'self'",
        "script-src 'self' https://cdn.jsdelivr.net",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com",
        "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com",
        "img-src 'self' data: blob:",
        'frame-src https://www.google.com https://maps.google.com',
        "connect-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Header versi PHP dipasang oleh PHP sendiri (expose_php), bukan oleh response Laravel.
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }
        $response->headers->remove('X-Powered-By');

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Halaman error debug Laravel memakai script inline; jangan diblokir saat APP_DEBUG=true.
        if (! (config('app.debug') && $response->getStatusCode() >= 500)) {
            $response->headers->set('Content-Security-Policy', implode('; ', self::CONTENT_SECURITY_POLICY));
        }

        // HSTS hanya bermakna lewat HTTPS (production).
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
