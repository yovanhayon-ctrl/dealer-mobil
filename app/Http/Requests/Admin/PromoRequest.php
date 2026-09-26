<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\NormalizesDigits;
use App\Models\Car;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class PromoRequest extends FormRequest
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
     * Diskon boleh ditulis dengan titik ribuan ("15.000.000"); pilihan mobil kosong = promo umum.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => Str::squish((string) $this->input('title')),
            'car_id' => $this->filled('car_id') ? $this->input('car_id') : null,
            'discount_amount' => $this->digitsOnly('discount_amount'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'car_id' => ['nullable', 'integer', Rule::exists('cars', 'id')],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            // SVG sengaja tidak diizinkan (bisa berisi script).
            'image' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2048)
                    ->dimensions(Rule::dimensions()->minWidth(1200)->minHeight(400)),
            ],
            'remove_image' => ['nullable', 'boolean'],
            // Diskon hanya untuk promo khusus mobil; promo umum bersifat informasi.
            'discount_amount' => $this->input('car_id') !== null
                ? ['required', 'integer', 'min:1', 'max:999999999999']
                : ['prohibited'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Aturan yang butuh data mobil: mobil harus aktif (kecuali mobil lama promo yang diedit)
     * dan diskon harus lebih kecil dari harga mobil.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('car_id') === null || $validator->errors()->has('car_id')) {
                    return;
                }

                $car = Car::find($this->input('car_id'));
                $currentCarId = (int) $this->route('promo')?->car_id;

                if (! $car->is_active && $car->id !== $currentCarId) {
                    $validator->errors()->add('car_id', 'Mobil yang dipilih sedang nonaktif.');

                    return;
                }

                if (! $validator->errors()->has('discount_amount') && (int) $this->input('discount_amount') >= $car->price) {
                    $price = number_format($car->price, 0, ',', '.');
                    $validator->errors()->add('discount_amount', "Diskon harus lebih kecil dari harga mobil (Rp {$price}).");
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'car_id' => 'mobil',
            'title' => 'judul promo',
            'description' => 'deskripsi',
            'image' => 'banner',
            'discount_amount' => 'diskon',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.image' => 'Banner harus berupa gambar JPG, JPEG, PNG, atau WEBP.',
            'image.mimes' => 'Banner harus berupa gambar JPG, JPEG, PNG, atau WEBP.',
            'image.max' => 'Ukuran banner maksimal 2 MB.',
            'image.dimensions' => 'Banner minimal berukuran 1200×400 piksel.',
            'discount_amount.required' => 'Diskon wajib diisi untuk promo khusus mobil.',
            'discount_amount.min' => 'Diskon minimal Rp 1.',
            'discount_amount.prohibited' => 'Diskon hanya untuk promo khusus mobil. Kosongkan untuk promo umum.',
            'start_date.date_format' => 'Tanggal mulai tidak valid.',
            'end_date.date_format' => 'Tanggal selesai tidak valid.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }

    /**
     * Data siap simpan (tanpa slug & banner). is_active dari checkbox: tidak dicentang = false.
     *
     * @return array<string, mixed>
     */
    public function promoData(): array
    {
        $data = $this->safe()->only(['car_id', 'title', 'description', 'discount_amount', 'start_date', 'end_date']);

        return [
            ...$data,
            'car_id' => $data['car_id'] ?? null,
            'description' => $data['description'] ?? null,
            'discount_amount' => $data['discount_amount'] ?? null,
            'is_active' => $this->boolean('is_active'),
        ];
    }
}
