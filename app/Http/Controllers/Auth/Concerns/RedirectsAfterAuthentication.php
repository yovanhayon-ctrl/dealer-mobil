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

        $previous = url()->previous();
        $excluded = [route('login'), route('register')];

        if (str_starts_with($previous, url('/')) && ! in_array($previous, $excluded, true)) {
            $request->session()->put('url.intended', $previous);
        }
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
            $isAdminArea = str_starts_with($intended, url('/admin'));
            $redirect = redirect()->to($isAdminArea ? route('home') : $intended);
        }

        return $message ? $redirect->with('success', $message) : $redirect;
    }
}
