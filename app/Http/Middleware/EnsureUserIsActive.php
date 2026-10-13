<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Akun yang dinonaktifkan owner langsung keluar, walaupun sesinya masih ada.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan. Hubungi pemilik dealer.');
        }

        return $next($request);
    }
}
