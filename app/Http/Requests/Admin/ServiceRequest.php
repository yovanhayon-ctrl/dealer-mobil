<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesDigits;
use App\Rules\UniqueIgnoringCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ServiceRequest extends FormRequest
{
    use NormalizesDigits;

    /**
     * Akses sudah dibatasi middleware auth + admin pada grup route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Harga boleh ditulis dengan titik ribuan ("450.000").
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'price_from' => $this->digitsOnly('price_from'),
            'duration_minutes' => $this->digitsOnly('duration_minutes'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                new UniqueIgnoringCase('services', 'name', $this->route('service')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            // Kosong = harga tidak ditampilkan ("Hubungi dealer").
            'price_from' => ['nullable', 'integer', 'min:1', 'max:999999999999'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:1440'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama layanan',
            'description' => 'deskripsi',
            'price_from' => 'harga mulai',
            'duration_minutes' => 'estimasi durasi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price_from.min' => 'Harga mulai minimal Rp 1. Kosongkan jika harga tidak ingin ditampilkan.',
            'duration_minutes.min' => 'Estimasi durasi minimal 15 menit.',
            'duration_minutes.max' => 'Estimasi durasi maksimal 1440 menit (24 jam).',
        ];
    }

    /**
     * Data siap simpan (tanpa slug). is_active dari checkbox: tidak dicentang = false.
     *
     * @return array<string, mixed>
     */
    public function serviceData(): array
    {
        $data = $this->safe()->only(['name', 'description', 'price_from', 'duration_minutes']);

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_from' => $data['price_from'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
