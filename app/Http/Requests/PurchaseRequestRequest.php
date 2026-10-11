<?php

namespace App\Http\Requests;

use App\Actions\SubmitPurchaseRequest;
use App\Http\Requests\Concerns\NormalizesDigits;
use App\Http\Requests\Concerns\NormalizesPhone;
use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Support\CreditCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PurchaseRequestRequest extends FormRequest
{
    use NormalizesDigits, NormalizesPhone;

    public const ADMIN_MESSAGE = 'Akun admin tidak dapat mengajukan pembelian. Gunakan akun customer.';

    public const MAX_ADDRESS_LENGTH = 500;

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
            redirect()->route('purchase-requests.create', $this->car())->with('error', self::ADMIN_MESSAGE)
        );
    }

    /**
     * DP boleh memakai titik ribuan; untuk cash, DP & tenor diabaikan.
     */
    protected function prepareForValidation(): void
    {
        $isCredit = $this->input('payment_method') === PurchaseRequest::PAYMENT_CREDIT;
        $trim = fn (string $key) => is_string($this->input($key)) ? trim($this->input($key)) : $this->input($key);

        $this->merge([
            'down_payment' => $isCredit ? $this->digitsOnly('down_payment') : null,
            'tenor_months' => $isCredit ? $this->input('tenor_months') : null,
            'phone' => $this->normalizePhone($this->input('phone')),
            'address' => $trim('address'),
            'notes' => $trim('notes'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(PurchaseRequest::PAYMENT_METHODS)],
            'down_payment' => ['nullable', 'required_if:payment_method,'.PurchaseRequest::PAYMENT_CREDIT, 'integer', 'min:1', 'max:999999999999'],
            'tenor_months' => ['nullable', 'required_if:payment_method,'.PurchaseRequest::PAYMENT_CREDIT, 'integer', Rule::in(app(CreditCalculator::class)->tenors())],
            'phone' => ['required', 'regex:'.self::PHONE_REGEX],
            'address' => ['required', 'string', 'min:10', 'max:'.self::MAX_ADDRESS_LENGTH],
            'notes' => ['nullable', 'string', 'max:'.self::MAX_NOTES_LENGTH],
        ];
    }

    /**
     * Aturan mobil, pengajuan ganda, dan batas DP (dicek ulang di SubmitPurchaseRequest dengan lock).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['payment_method', 'down_payment', 'tenor_months'])) {
                    return;
                }

                $conflicts = app(SubmitPurchaseRequest::class)->conflicts(
                    $this->user(),
                    $this->car()->loadMissing('activePromos'),
                    $this->only(['payment_method', 'down_payment']),
                );

                foreach ($conflicts as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            },
        ];
    }

    /**
     * @return array{payment_method: string, down_payment: ?int, tenor_months: ?int, phone: string, address: string, notes: ?string}
     */
    public function purchaseData(): array
    {
        $data = $this->validated();

        return [
            'payment_method' => $data['payment_method'],
            'down_payment' => isset($data['down_payment']) ? (int) $data['down_payment'] : null,
            'tenor_months' => isset($data['tenor_months']) ? (int) $data['tenor_months'] : null,
            'phone' => $data['phone'],
            'address' => $data['address'],
            'notes' => ($data['notes'] ?? null) ?: null,
        ];
    }

    public function car(): Car
    {
        return $this->route('car');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payment_method' => 'metode pembayaran',
            'down_payment' => 'uang muka',
            'tenor_months' => 'tenor',
            'phone' => 'nomor WhatsApp',
            'address' => 'alamat',
            'notes' => 'catatan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'Pilih metode pembayaran.',
            'payment_method.in' => 'Pilih metode pembayaran Cash atau Kredit.',
            'down_payment.required_if' => 'Uang muka wajib diisi untuk pembayaran kredit.',
            'tenor_months.required_if' => 'Pilih tenor untuk pembayaran kredit.',
            'tenor_months.in' => 'Tenor tidak tersedia.',
            'phone.regex' => 'Nomor WhatsApp tidak valid. Gunakan format 08xx (10-13 digit).',
            'address.min' => 'Alamat terlalu pendek, tuliskan alamat lengkap.',
        ];
    }
}
