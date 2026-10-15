<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Owner menambah / mengubah akun staf (admin atau mekanik). Akun owner tidak dikelola di sini.
 */
class StaffRequest extends FormRequest
{
    use NormalizesPhone;

    /**
     * Akses dibatasi middleware can:staff.manage pada grup route; role yang boleh diberikan dibatasi di rules().
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'phone' => filled($this->input('phone')) ? $this->normalizePhone($this->input('phone')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $staff = $this->route('user');

        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff?->id)],
            'phone' => ['nullable', 'regex:'.self::PHONE_REGEX],
            'role' => ['required', Rule::in($this->user()->manageableStaffRoles())],
            // Wajib saat membuat akun; saat mengubah, kosong = kata sandi tetap.
            'password' => [$staff ? 'nullable' : 'required', 'string', 'confirmed', Password::min(8)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'phone' => 'nomor WhatsApp',
            'role' => 'role',
            'password' => 'kata sandi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'phone.regex' => 'Nomor WhatsApp tidak valid. Gunakan format 08xx (10-13 digit).',
            'role.in' => 'Pilih role Admin atau Mekanik.',
        ];
    }
}
