<?php

namespace App\Http\Requests\Admin;

use App\Models\PurchaseRequest;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi input perubahan status pengajuan. Transisi & stok diperiksa ulang di
 * App\Actions\ChangePurchaseRequestStatus (di dalam transaksi dengan lockForUpdate).
 */
class PurchaseRequestStatusRequest extends FormRequest
{
    /**
     * Akses sudah dibatasi middleware auth + admin pada grup route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Status kosong atau sama dengan status sekarang = status tetap (hanya catatan yang diubah).
     */
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');

        $this->merge([
            'status' => blank($status) || $status === $this->purchaseRequest()->status ? null : $status,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $purchaseRequest = $this->purchaseRequest();

        return [
            'status' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail) use ($purchaseRequest) {
                if (! is_string($value) || ! $purchaseRequest->canTransitionTo($value)) {
                    $to = PurchaseRequest::STATUS_LABELS[is_string($value) ? $value : ''] ?? 'status tersebut';
                    $fail("Status pengajuan tidak bisa diubah dari {$purchaseRequest->statusLabel()} ke {$to}.");
                }
            }],
            'admin_note' => [
                Rule::requiredIf(fn () => in_array($this->resultingStatus(), PurchaseRequest::NOTE_REQUIRED_STATUSES, true)),
                'nullable', 'string', 'min:5', 'max:1000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'status',
            'admin_note' => 'catatan admin',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admin_note.required' => 'Catatan admin wajib diisi saat menolak atau membatalkan pengajuan (alasan untuk customer).',
        ];
    }

    private function purchaseRequest(): PurchaseRequest
    {
        return $this->route('purchaseRequest');
    }

    private function resultingStatus(): string
    {
        return $this->input('status') ?? $this->purchaseRequest()->status;
    }
}
