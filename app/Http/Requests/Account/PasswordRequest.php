<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Ganti kata sandi: wajib kata sandi saat ini, kata sandi baru minimal 8 karakter dan berbeda.
 */
class PasswordRequest extends FormRequest
{
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
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::min(8), 'different:current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'kata sandi saat ini',
            'password' => 'kata sandi baru',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Kata sandi saat ini salah.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
        ];
    }
}
