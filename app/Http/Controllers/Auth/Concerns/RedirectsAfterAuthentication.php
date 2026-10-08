<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RedirectsAfterAuthentication
{
    /**
     * Simpan halaman asal (internal, bukan halaman login/register) sebagai tujuan
     * setelah login, jika belum ada tujuan dari middleware auth.
     */
    protected function rememberPreviousUrl(Request $request): void
    {
        if ($request->session()->has('url.intended')) {
            return;
        }

        // url()->previous() bisa berasal dari header Referer (dikirim browser, bisa dari situs lain).
        $previous = url()->previous();
        $excluded = [route('login'), route('register')];

        if ($this->isInternalUrl($previous) && ! in_array($previous, $excluded, true)) {
            $request->session()->put('url.intended', $previous);
        }
    }

    /**
     * true bila skema, host, dan port sama persis dengan aplikasi. Pengecekan awalan string tidak cukup:
     * "http://dealer-mobil.test.evil.example" juga diawali "http://dealer-mobil.test" (open redirect).
     */
    protected function isInternalUrl(string $url): bool
    {
        $target = parse_url($url);
        $app = parse_url(url('/'));

        if (! is_array($target) || ! is_array($app) || ! isset($target['scheme'], $target['host'])) {
            return false;
        }

        return strtolower($target['scheme']) === strtolower($app['scheme'] ?? '')
            && strtolower($target['host']) === strtolower($app['host'] ?? '')
            && ($target['port'] ?? null) === ($app['port'] ?? null)
            && ! isset($target['user']) && ! isset($target['pass']);
    }

    /**
     * Admin selalu ke dashboard. Customer ke halaman sebelumnya atau beranda,
     * tetapi tidak pernah ke area /admin.
     */
    protected function redirectAfterAuthentication(Request $request, User $user, ?string $message = null): RedirectResponse
    {
        $intended = $request->session()->pull('url.intended', route('home'));

        if ($user->isAdmin()) {
            $redirect = redirect()->route('admin.dashboard');
        } else {
            $path = (string) parse_url($intended, PHP_URL_PATH);
            $isAdminArea = $path === '/admin' || str_starts_with($path, '/admin/');
            // Lapisan kedua: tujuan di luar aplikasi (dari sumber mana pun) diganti beranda.
            $redirect = redirect()->to($isAdminArea || ! $this->isInternalUrl($intended) ? route('home') : $intended);
        }

        return $message ? $redirect->with('success', $message) : $redirect;
    }
}
