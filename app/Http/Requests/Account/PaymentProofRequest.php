<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\Concerns\NormalizesDigits;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class PaymentProofRequest extends FormRequest
{
    use NormalizesDigits;

    /** Foto bukti transfer langsung dari HP atau PDF dari m-banking (maks. 8 MB). */
    public const MAX_KILOBYTES = 8 * 1024;

    public const MAX_NOTE_LENGTH = 500;

    /**
     * Pengajuan milik customer lain: 404 sebelum validasi (pesan validasi memuat nominal DP/harga).
     * Status pengajuan diperiksa di controller.
     */
    public function authorize(): bool
    {
        abort_unless($this->purchaseRequest()->user_id === $this->user()->id, 404);

        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->digitsOnly('amount'),
            'note' => trim((string) $this->input('note')) ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $purchase = $this->purchaseRequest();

        return [
            'type' => ['required', Rule::in(array_keys($purchase->paymentProofTypes()))],
            'amount' => ['required', 'integer', 'min:1', 'max:'.$purchase->amountPayableToDealer()],
            'paid_on' => [
                'required', 'date_format:Y-m-d',
                'after_or_equal:'.$purchase->created_at->toDateString(),
                'before_or_equal:'.today()->toDateString(),
            ],
            // Jenis file dicek dari isinya, bukan hanya ekstensi. SVG/HTML tidak diizinkan.
            'file' => ['required', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(self::MAX_KILOBYTES)],
            'note' => ['nullable', 'string', 'max:'.self::MAX_NOTE_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'jenis pembayaran',
            'amount' => 'nominal',
            'paid_on' => 'tanggal transfer',
            'file' => 'file bukti',
            'note' => 'catatan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $purchase = $this->purchaseRequest();

        return [
            'amount.max' => 'Nominal tidak boleh melebihi Rp '.number_format($purchase->amountPayableToDealer(), 0, ',', '.')
                .($purchase->isCredit() ? ' (DP pengajuan ini).' : ' (harga mobil).'),
            'paid_on.after_or_equal' => 'Tanggal transfer tidak boleh sebelum tanggal pengajuan.',
            'paid_on.before_or_equal' => 'Tanggal transfer tidak boleh di masa depan.',
            'file.mimes' => 'File bukti harus berupa gambar JPG, PNG, WEBP, atau PDF.',
            'file.max' => 'Ukuran file bukti maksimal '.intdiv(self::MAX_KILOBYTES, 1024).' MB.',
            'file.uploaded' => 'File bukti gagal diunggah. Pastikan ukurannya maksimal '.intdiv(self::MAX_KILOBYTES, 1024).' MB.',
        ];
    }

    private function purchaseRequest(): PurchaseRequest
    {
        return $this->route('purchaseRequest');
    }
}
