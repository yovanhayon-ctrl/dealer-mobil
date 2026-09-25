<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi nomor HP: buang spasi/tanda hubung/titik, ubah +62/62 menjadi 0.
     */
    protected function prepareForValidation(): void
    {
        $phone = preg_replace('/[\s\-.()]/', '', (string) $this->input('phone'));

        if (str_starts_with($phone, '+62')) {
            $phone = '0'.substr($phone, 3);
        } elseif (str_starts_with($phone, '62')) {
            $phone = '0'.substr($phone, 2);
        }

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => $phone,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // 08 + 8–11 digit (total 10–13 digit), awalan operator 081–089.
            'phone' => ['required', 'regex:/^08[1-9][0-9]{7,10}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
