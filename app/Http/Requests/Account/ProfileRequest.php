<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Ubah data diri (nama, email, nomor WhatsApp). Aturan sama dengan registrasi; role tidak bisa diubah.
 */
class ProfileRequest extends FormRequest
{
    use NormalizesPhone;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => $this->normalizePhone($this->input('phone')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone' => ['required', 'regex:'.self::PHONE_REGEX],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['phone' => 'nomor WhatsApp'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'phone.regex' => 'Nomor WhatsApp tidak valid. Gunakan format 08xx (10-13 digit).',
        ];
    }
}
