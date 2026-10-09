<?php

namespace App\Http\Requests\Account;

use App\Models\Testimonial;
use Illuminate\Foundation\Http\FormRequest;

class TestimonialRequest extends FormRequest
{
    /**
     * Kepemilikan & status pengajuan diperiksa di controller (404 / pesan).
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['comment' => trim((string) $this->input('comment'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:'.Testimonial::MIN_RATING.','.Testimonial::MAX_RATING],
            'comment' => ['required', 'string', 'min:20', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rating' => 'rating',
            'comment' => 'ulasan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Pilih rating 1 sampai 5 bintang.',
            'rating.integer' => 'Pilih rating 1 sampai 5 bintang.',
            'rating.between' => 'Pilih rating 1 sampai 5 bintang.',
        ];
    }
}
