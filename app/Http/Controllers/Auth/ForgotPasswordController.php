<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Lupa kata sandi: kirim tautan reset (password broker bawaan Laravel, tabel password_reset_tokens).
 * Di lokal MAIL_MAILER=log, jadi email (berisi tautan) ditulis ke storage/logs/laravel.log.
 */
class ForgotPasswordController extends Controller
{
    /** Pesan selalu sama agar tidak bisa dipakai menebak email mana yang terdaftar. */
    public const GENERIC_MESSAGE = 'Jika email tersebut terdaftar, tautan reset kata sandi sudah dikirim. Periksa kotak masuk Anda.';

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);

        // Hasil (terkirim / email tidak ada / throttled) sengaja tidak ditampilkan.
        Password::sendResetLink($request->only('email'));

        return redirect()->route('password.request')->with('success', self::GENERIC_MESSAGE);
    }
}
