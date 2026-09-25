<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\RedirectsAfterAuthentication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    use RedirectsAfterAuthentication;

    public function create(Request $request): View
    {
        $this->rememberPreviousUrl($request);

        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->only(['name', 'email', 'phone', 'password']));
        // role tidak ada di $fillable, jadi diisi eksplisit.
        $user->role = User::ROLE_CUSTOMER;
        $user->save();

        Auth::login($user);

        $request->session()->regenerate();

        return $this->redirectAfterAuthentication($request, $user, 'Pendaftaran berhasil. Selamat datang!');
    }
}
