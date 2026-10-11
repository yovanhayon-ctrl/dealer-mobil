<?php

namespace App\Http\Requests;

use App\Actions\BookTestDrive;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Models\Car;
use App\Models\TestDrive;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TestDriveRequest extends FormRequest
{
    use NormalizesPhone;

    public const ADMIN_MESSAGE = 'Akun admin tidak dapat booking test drive. Gunakan akun customer.';

    public const MAX_NOTES_LENGTH = 500;

    /**
     * Hanya customer; admin diarahkan kembali dengan pesan (bukan halaman 403).
     */
    public function authorize(): bool
    {
        return ! $this->user()->isAdmin();
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(
            redirect()->route('test-drives.create')->with('error', self::ADMIN_MESSAGE)
        );
    }

    protected function prepareForValidation(): void
    {
        $notes = $this->input('notes');

        $this->merge([
            'phone' => $this->normalizePhone($this->input('phone')),
            'notes' => is_string($notes) ? trim($notes) : $notes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'car_id' => ['required', 'integer', 'exists:cars,id'],
            // Tanggal WIB (timezone aplikasi Asia/Jakarta): besok s/d hari ini + 30 hari.
            'preferred_date' => [
                'required', 'date_format:Y-m-d',
                'after_or_equal:'.self::firstDate(),
                'before_or_equal:'.self::lastDate(),
            ],
            'preferred_time' => ['required', 'string', Rule::in(TestDrive::TIME_SLOTS)],
            'phone' => ['required', 'regex:'.self::PHONE_REGEX],
            'notes' => ['nullable', 'string', 'max:'.self::MAX_NOTES_LENGTH],
        ];
    }

    /**
     * Aturan mobil, booking ganda, dan bentrok slot (dicek ulang di BookTestDrive dengan lock).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['car_id', 'preferred_date', 'preferred_time'])) {
                    return;
                }

                $conflicts = app(BookTestDrive::class)->conflicts(
                    $this->user(),
                    Car::findOrFail($this->integer('car_id')),
                    $this->string('preferred_date')->value(),
                    $this->string('preferred_time')->value(),
                );

                foreach ($conflicts as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            },
        ];
    }

    public static function firstDate(): string
    {
        return today()->addDays(TestDrive::MIN_DAYS_AHEAD)->toDateString();
    }

    public static function lastDate(): string
    {
        return today()->addDays(TestDrive::MAX_DAYS_AHEAD)->toDateString();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'car_id' => 'mobil',
            'preferred_date' => 'tanggal',
            'preferred_time' => 'jam',
            'phone' => 'nomor WhatsApp',
            'notes' => 'catatan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $first = today()->addDays(TestDrive::MIN_DAYS_AHEAD)->translatedFormat('d M Y');
        $last = today()->addDays(TestDrive::MAX_DAYS_AHEAD)->translatedFormat('d M Y');

        return [
            'car_id.required' => 'Pilih mobil yang ingin di-test drive.',
            'car_id.exists' => 'Mobil tidak ditemukan.',
            'preferred_date.after_or_equal' => "Tanggal test drive paling cepat besok ({$first}).",
            'preferred_date.before_or_equal' => "Tanggal test drive paling lambat {$last}.",
            'preferred_time.in' => 'Pilih jam antara 09:00 dan 16:00 WIB.',
            'phone.regex' => 'Nomor WhatsApp tidak valid. Gunakan format 08xx (10-13 digit).',
        ];
    }
}
