<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Maksimal percobaan login gagal per menit untuk kombinasi email + IP.
     */
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Coba login dengan rate limit.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Akun staf yang dinonaktifkan owner dianggap gagal login (pesan khusus di bawah).
        if (! Auth::attemptWhen($this->only('email', 'password'), fn ($user) => $user->isActive(), $this->boolean('remember'))) {
            $inactive = User::where('email', $this->input('email'))->where('is_active', false)->exists();

            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => $inactive && Auth::validate($this->only('email', 'password'))
                    ? 'Akun Anda dinonaktifkan. Hubungi pemilik dealer.'
                    : __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds]),
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
