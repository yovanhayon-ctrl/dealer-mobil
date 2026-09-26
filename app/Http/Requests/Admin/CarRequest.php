<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesDigits;
use App\Models\Car;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CarRequest extends FormRequest
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
     * Harga & km boleh ditulis dengan titik ribuan ("285.000.000"); mobil baru selalu 0 km.
     */
    protected function prepareForValidation(): void
    {
        $data = [
            'name' => Str::squish((string) $this->input('name')),
            'color' => Str::squish((string) $this->input('color')) ?: null,
            'price' => $this->digitsOnly('price'),
            'mileage' => $this->digitsOnly('mileage'),
        ];

        if ($this->input('vehicle_condition') === Car::CONDITION_NEW) {
            $data['mileage'] = 0;
        }

        $this->merge($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isUsed = $this->input('vehicle_condition') === Car::CONDITION_USED;

        return [
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'vehicle_condition' => ['required', Rule::in(array_keys(Car::CONDITIONS))],
            'mileage' => $isUsed
                ? ['required', 'integer', 'min:1', 'max:2000000']
                : ['nullable', 'integer', 'min:0'],
            'year' => ['required', 'integer', 'between:1990,'.(now()->year + 1)],
            'price' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'transmission' => ['required', Rule::in(array_keys(Car::TRANSMISSIONS))],
            'fuel_type' => ['required', Rule::in(array_keys(Car::FUEL_TYPES))],
            'engine_cc' => ['nullable', 'integer', 'between:500,10000'],
            'seats' => ['required', 'integer', 'between:2,9'],
            'color' => ['nullable', 'string', 'max:50'],
            'stock' => ['required', 'integer', 'min:0', 'max:9999'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'brand_id' => 'merek',
            'category_id' => 'kategori',
            'name' => 'nama mobil',
            'vehicle_condition' => 'kondisi',
            'mileage' => 'kilometer',
            'year' => 'tahun',
            'price' => 'harga',
            'transmission' => 'transmisi',
            'fuel_type' => 'bahan bakar',
            'engine_cc' => 'kapasitas mesin',
            'seats' => 'jumlah kursi',
            'color' => 'warna',
            'stock' => 'stok',
            'description' => 'deskripsi',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mileage.required' => 'Kilometer wajib diisi untuk mobil bekas.',
            'mileage.min' => 'Kilometer mobil bekas minimal 1 km.',
            'price.min' => 'Harga minimal Rp 1.',
        ];
    }

    /**
     * Data siap simpan (tanpa slug). is_active dari checkbox: tidak dicentang = false.
     *
     * @return array<string, mixed>
     */
    public function carData(): array
    {
        return [
            ...$this->safe()->except('is_active'),
            'mileage' => (int) $this->validated('mileage', 0),
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
