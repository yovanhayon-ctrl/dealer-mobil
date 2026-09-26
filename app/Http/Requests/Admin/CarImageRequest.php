<?php

namespace App\Http\Requests\Admin;

use App\Models\CarImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class CarImageRequest extends FormRequest
{
    private ?int $remainingSlots = null;

    /**
     * Akses sudah dibatasi middleware auth + admin pada grup route.
     */
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
            'images' => ['required', 'array', 'max:'.$this->remainingSlots()],
            // SVG sengaja tidak diizinkan (bisa berisi script).
            'images.*' => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2048)
                    ->dimensions(Rule::dimensions()->minWidth(600)->minHeight(400)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $remaining = $this->remainingSlots();
        $max = CarImage::MAX_PER_CAR;

        return [
            'images.required' => 'Pilih minimal satu gambar.',
            'images.array' => 'Pilih minimal satu gambar.',
            'images.max' => $remaining === 0
                ? "Galeri sudah penuh ({$max} gambar). Hapus gambar lain terlebih dahulu."
                : "Maksimal {$max} gambar per mobil. Sisa slot: {$remaining} gambar.",
            'images.*.required' => 'Gambar ke-:position tidak valid.',
            'images.*.uploaded' => 'Gambar ke-:position gagal diunggah.',
            'images.*.file' => 'Gambar ke-:position gagal diunggah.',
            'images.*.image' => 'Gambar ke-:position harus berupa JPG, JPEG, PNG, atau WEBP.',
            'images.*.mimes' => 'Gambar ke-:position harus berupa JPG, JPEG, PNG, atau WEBP.',
            'images.*.max' => 'Ukuran gambar ke-:position maksimal 2 MB.',
            'images.*.dimensions' => 'Gambar ke-:position minimal berukuran 600×400 piksel.',
        ];
    }

    /**
     * Sisa slot gambar mobil ini (maksimal CarImage::MAX_PER_CAR per mobil).
     */
    private function remainingSlots(): int
    {
        return $this->remainingSlots ??= max(0, CarImage::MAX_PER_CAR - $this->route('car')->images()->count());
    }
}
