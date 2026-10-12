<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Account\PaymentProofRequest;
use App\Http\Requests\Concerns\NormalizesDigits;
use App\Models\PaymentProof;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Admin mencatat pembayaran langsung di showroom (tunai / EDC / transfer di tempat); langsung berstatus diterima.
 */
class ShowroomPaymentRequest extends FormRequest
{
    use NormalizesDigits;

    /** Error ditampilkan di form catat pembayaran (kartu Bukti Pembayaran), bukan form status. */
    protected $errorBag = 'showroomPayment';

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
            'type' => ['required', Rule::in(array_keys($purchase->paymentProofTypes()))],
            'method' => ['required', Rule::in(PaymentProof::SHOWROOM_METHODS)],
            // Tidak boleh melebihi sisa tagihan (harga untuk cash, DP untuk kredit).
            'amount' => ['required', 'integer', 'min:1', 'max:'.max(1, $purchase->remainingPayment())],
            'paid_on' => [
                'required', 'date_format:Y-m-d',
                'after_or_equal:'.$purchase->created_at->toDateString(),
                'before_or_equal:'.today()->toDateString(),
            ],
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
            'type' => 'jenis pembayaran',
            'method' => 'cara bayar',
            'amount' => 'nominal',
            'paid_on' => 'tanggal bayar',
            'file' => 'foto kwitansi',
            'note' => 'catatan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.max' => 'Nominal melebihi sisa tagihan Rp '.number_format($this->purchaseRequest()->remainingPayment(), 0, ',', '.').'.',
            'paid_on.after_or_equal' => 'Tanggal bayar tidak boleh sebelum tanggal pengajuan.',
            'paid_on.before_or_equal' => 'Tanggal bayar tidak boleh di masa depan.',
            'file.mimes' => 'Foto kwitansi harus berupa gambar JPG, PNG, WEBP, atau PDF.',
            'file.max' => 'Ukuran foto kwitansi maksimal '.intdiv(PaymentProofRequest::MAX_KILOBYTES, 1024).' MB.',
        ];
    }

    private function purchaseRequest(): PurchaseRequest
    {
        return $this->route('purchaseRequest');
    }
}
