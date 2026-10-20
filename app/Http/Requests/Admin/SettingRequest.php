<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\PageController;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Models\Setting;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

/**
 * Admin > Pengaturan: profil dealer, teks "Siapa Kami", rekening pembayaran, dan hero beranda.
 * Akses dibatasi middleware can:settings.manage (Owner & Super Admin).
 */
class SettingRequest extends FormRequest
{
    use NormalizesPhone;

    public const MAX_KILOBYTES = 8192;

    public const HERO_BACKGROUNDS = [
        'video' => 'Video',
        'image' => 'Gambar banner',
        'car' => 'Foto mobil unggulan',
    ];

    /** Field form => kunci pengaturan (Setting::KEYS). Gambar hero diproses terpisah di controller. */
    private const FIELDS = [
        'name' => 'name',
        'tagline' => 'tagline',
        'address' => 'address',
        'phone' => 'phone',
        'whatsapp' => 'whatsapp',
        'email' => 'email',
        'hours' => 'hours',
        'maps_embed_url' => 'maps_embed_url',
        'about_text' => 'about_text',
        'bank_name' => 'bank.name',
        'bank_account' => 'bank.account',
        'bank_holder' => 'bank.holder',
        'hero_kicker' => 'hero.kicker',
        'hero_title' => 'hero.title',
        'hero_lead' => 'hero.lead',
        'hero_background' => 'hero.background',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        foreach (array_keys(self::FIELDS) as $field) {
            $value = $this->input($field);
            $value = is_string($value) ? trim($value) : null;
            // Teks panjang: rapikan akhir baris & batasi baris kosong beruntun; teks lain: satu baris.
            $values[$field] = $field === 'about_text'
                ? preg_replace("/\n{3,}/", "\n\n", str_replace("\r\n", "\n", (string) $value))
                : Str::squish((string) $value);
        }

        $values['email'] = strtolower($values['email']);
        $values['whatsapp'] = filled($values['whatsapp']) ? $this->normalizePhone($values['whatsapp']) : '';
        $values['bank_account'] = preg_replace('/[\s\-.]/', '', $values['bank_account']);

        $this->merge($values);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s\-().]{5,}$/'],
            'whatsapp' => ['nullable', 'regex:'.self::PHONE_REGEX],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'hours' => ['nullable', 'string', 'max:100'],
            'maps_embed_url' => ['nullable', 'string', 'max:2000', function (string $attribute, mixed $value, Closure $fail) {
                if (PageController::mapsEmbedUrl($value) === null) {
                    $fail('Gunakan link "Sematkan peta" dari Google Maps (diawali https://www.google.com/maps/embed?).');
                }
            }],
            'about_text' => ['nullable', 'string', 'max:3000'],
            'bank_name' => ['nullable', 'string', 'max:50'],
            'bank_account' => ['nullable', 'string', 'regex:/^[0-9]{5,30}$/'],
            'bank_holder' => ['nullable', 'string', 'max:100'],
            'hero_kicker' => ['nullable', 'string', 'max:60'],
            'hero_title' => ['required', 'string', 'max:90'],
            'hero_lead' => ['nullable', 'string', 'max:200'],
            'hero_background' => ['required', Rule::in(array_keys(self::HERO_BACKGROUNDS))],
            // SVG sengaja tidak diizinkan (bisa berisi script).
            'hero_image' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(self::MAX_KILOBYTES)
                    ->dimensions(Rule::dimensions()->minWidth(1200)->minHeight(500)->maxWidth(8000)->maxHeight(8000)),
            ],
            'remove_hero_image' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Latar "gambar" butuh gambar banner: unggahan baru atau gambar tersimpan yang tidak dihapus.
     *
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $hasImage = $this->hasFile('hero_image')
                    || (filled(Setting::values()['hero.image'] ?? null) && ! $this->boolean('remove_hero_image'));

                if ($this->input('hero_background') === 'image' && ! $hasImage && ! $validator->errors()->has('hero_image')) {
                    $validator->errors()->add('hero_image', 'Unggah gambar banner untuk latar Gambar banner.');
                }

                // Rekening dipakai bersama: isi ketiganya atau kosongkan semuanya.
                $bank = array_filter([$this->input('bank_name'), $this->input('bank_account'), $this->input('bank_holder')], 'filled');

                if ($bank !== [] && count($bank) < 3) {
                    $validator->errors()->add('bank_name', 'Lengkapi nama bank, nomor rekening, dan atas nama (atau kosongkan ketiganya).');
                }
            },
        ];
    }

    /**
     * Nilai pengaturan (kunci Setting) siap disimpan. Nilai yang sama dengan bawaan .env disimpan kosong (memakai bawaan).
     *
     * @return array<string, ?string>
     */
    public function settings(): array
    {
        $values = [];

        foreach (self::FIELDS as $field => $key) {
            $value = $this->validated($field);
            $value = $key === 'whatsapp' && filled($value) ? '62'.substr($value, 1) : $value;

            $values[$key] = filled($value) && $value !== (string) Setting::original($key) ? $value : null;
        }

        return $values;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama dealer',
            'address' => 'alamat',
            'phone' => 'telepon',
            'whatsapp' => 'nomor WhatsApp',
            'hours' => 'jam operasional',
            'maps_embed_url' => 'link Google Maps',
            'about_text' => 'teks Siapa Kami',
            'bank_name' => 'nama bank',
            'bank_account' => 'nomor rekening',
            'bank_holder' => 'atas nama',
            'hero_kicker' => 'teks kecil di atas judul',
            'hero_title' => 'judul hero',
            'hero_lead' => 'teks di bawah judul',
            'hero_background' => 'latar hero',
            'hero_image' => 'gambar banner',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor telepon tidak valid (hanya angka, spasi, tanda hubung, atau kurung).',
            'whatsapp.regex' => 'Nomor WhatsApp tidak valid. Gunakan nomor HP, mis. 0812xxxx atau 62812xxxx.',
            'bank_account.regex' => 'Nomor rekening hanya berisi angka (5-30 digit).',
            'hero_image.image' => 'Gambar banner harus berupa JPG, JPEG, PNG, atau WEBP.',
            'hero_image.mimes' => 'Gambar banner harus berupa JPG, JPEG, PNG, atau WEBP.',
            'hero_image.max' => 'Ukuran gambar banner maksimal '.intdiv(self::MAX_KILOBYTES, 1024).' MB.',
            'hero_image.dimensions' => 'Gambar banner minimal berukuran 1200×500 piksel dan maksimal 8000×8000 piksel.',
        ];
    }
}
