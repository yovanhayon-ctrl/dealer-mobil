<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\PasswordRequest;
use App\Http\Requests\Account\ProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profil akun yang login (customer maupun admin): data diri & ganti kata sandi.
 * Role tidak bisa diubah dari sini.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            $user->loadCount(['testDrives', 'purchaseRequests', 'serviceBookings']);
        }

        return view('pages.account.profile.edit', ['user' => $user]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return redirect()->route('account.profile')->with('success', $user->wasChanged()
            ? 'Data diri berhasil diperbarui.'
            : 'Tidak ada perubahan data diri.');
    }

    public function updatePassword(PasswordRequest $request): RedirectResponse
    {
        // Cast "hashed" pada model User meng-hash kata sandi baru.
        $request->user()->update(['password' => $request->validated('password')]);

        // ID sesi baru agar sesi lama (mis. yang sempat tercuri) tidak bisa dipakai lagi.
        $request->session()->regenerate();

        return redirect()->route('account.profile')->with('success', 'Kata sandi berhasil diganti.');
    }
}
