<?php

namespace App\Http\Requests\Admin;

use App\Models\ServiceBooking;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ServiceBookingStatusRequest extends FormRequest
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
            'status' => blank($status) || $status === $this->serviceBooking()->status ? null : $status,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $booking = $this->serviceBooking();

        return [
            'status' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail) use ($booking) {
                if (! is_string($value) || ! $booking->canTransitionTo($value)) {
                    $to = ServiceBooking::STATUS_LABELS[is_string($value) ? $value : ''] ?? 'status tersebut';
                    $fail("Status booking servis tidak bisa diubah dari {$booking->statusLabel()} ke {$to}.");
                }
            }],
            'admin_note' => [
                Rule::requiredIf(fn () => in_array($this->resultingStatus(), ServiceBooking::NOTE_REQUIRED_STATUSES, true)),
                'nullable', 'string', 'min:5', 'max:1000',
            ],
        ];
    }

    /**
     * Kendaraan baru bisa mulai dikerjakan pada/sesudah tanggal jadwalnya.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('status')) {
                    return;
                }

                $booking = $this->serviceBooking();

                if ($this->input('status') === ServiceBooking::STATUS_IN_PROGRESS && $booking->preferred_date->isAfter(today())) {
                    $validator->errors()->add('status', 'Servis belum bisa mulai dikerjakan sebelum tanggal jadwalnya ('.$booking->preferred_date->translatedFormat('d M Y').').');
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
            'admin_note.required' => 'Catatan admin wajib diisi saat membatalkan booking servis (alasan untuk customer).',
        ];
    }

    private function serviceBooking(): ServiceBooking
    {
        return $this->route('serviceBooking');
    }

    /**
     * Status setelah disimpan: status baru, atau status sekarang bila tetap.
     */
    private function resultingStatus(): string
    {
        return $this->input('status') ?? $this->serviceBooking()->status;
    }
}
