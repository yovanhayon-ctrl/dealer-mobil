<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Account\PaymentProofRequest;
use App\Http\Requests\Concerns\NormalizesDigits;
use App\Models\PaymentRefund;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Admin mencatat dana yang dikembalikan ke customer (pengajuan Dibatalkan/Ditolak yang sempat menerima pembayaran).
 */
class PaymentRefundRequest extends FormRequest
{
    use NormalizesDigits;

    /** Error ditampilkan di form pengembalian dana (kartu Bukti Pembayaran). */
    protected $errorBag = 'refund';

    /**
     * Akses sudah dibatasi middleware auth + admin pada grup route.
     */
    public function authorize(): bool
    {
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
            // Tidak boleh melebihi dana diterima yang belum dikembalikan.
            'amount' => ['required', 'integer', 'min:1', 'max:'.max(1, $purchase->refundableAmount())],
            'refunded_on' => [
                'required', 'date_format:Y-m-d',
                'after_or_equal:'.$purchase->created_at->toDateString(),
                'before_or_equal:'.today()->toDateString(),
            ],
            'method' => ['required', Rule::in(array_keys(PaymentRefund::METHOD_LABELS))],
            'file' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'webp', 'pdf'])->max(PaymentProofRequest::MAX_KILOBYTES)],
            'note' => ['nullable', 'string', 'max:'.PaymentProofRequest::MAX_NOTE_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => 'nominal',
            'refunded_on' => 'tanggal pengembalian',
            'method' => 'cara pengembalian',
            'file' => 'bukti pengembalian',
            'note' => 'catatan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.max' => 'Nominal melebihi dana yang belum dikembalikan Rp '.number_format($this->purchaseRequest()->refundableAmount(), 0, ',', '.').'.',
            'refunded_on.after_or_equal' => 'Tanggal pengembalian tidak boleh sebelum tanggal pengajuan.',
            'refunded_on.before_or_equal' => 'Tanggal pengembalian tidak boleh di masa depan.',
            'file.mimes' => 'Bukti pengembalian harus berupa gambar JPG, PNG, WEBP, atau PDF.',
            'file.max' => 'Ukuran bukti pengembalian maksimal '.intdiv(PaymentProofRequest::MAX_KILOBYTES, 1024).' MB.',
        ];
    }

    private function purchaseRequest(): PurchaseRequest
    {
        return $this->route('purchaseRequest');
    }
}
