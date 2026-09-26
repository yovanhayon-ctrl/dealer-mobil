<?php

namespace App\Http\Requests\Admin;

use App\Models\TestDrive;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TestDriveStatusRequest extends FormRequest
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
            'status' => blank($status) || $status === $this->testDrive()->status ? null : $status,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $testDrive = $this->testDrive();

        return [
            'status' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail) use ($testDrive) {
                if (! is_string($value) || ! $testDrive->canTransitionTo($value)) {
                    $to = TestDrive::STATUS_LABELS[is_string($value) ? $value : ''] ?? 'status tersebut';
                    $fail("Status test drive tidak bisa diubah dari {$testDrive->statusLabel()} ke {$to}.");
                }
            }],
            'admin_note' => [
                Rule::requiredIf(fn () => in_array($this->resultingStatus(), TestDrive::NOTE_REQUIRED_STATUSES, true)),
                'nullable', 'string', 'min:5', 'max:1000',
            ],
        ];
    }

    /**
     * Aturan yang bergantung pada data mobil & tanggal (RANCANGAN §5).
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

                $testDrive = $this->testDrive();
                $status = $this->input('status');

                // Mobil bekas stok 0 = unit sudah terjual; mobil baru stok 0 tetap boleh (unit display).
                if ($status === TestDrive::STATUS_CONFIRMED && ! $testDrive->car->isNew() && ! $testDrive->car->inStock()) {
                    $validator->errors()->add('status', 'Unit mobil bekas ini sudah terjual (stok 0), test drive tidak bisa dikonfirmasi. Batalkan dengan catatan untuk customer.');
                }

                if ($status === TestDrive::STATUS_COMPLETED && $testDrive->preferred_date->isAfter(today())) {
                    $validator->errors()->add('status', 'Test drive belum bisa ditandai selesai sebelum tanggal jadwalnya ('.$testDrive->preferred_date->translatedFormat('d M Y').').');
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
            'admin_note.required' => 'Catatan admin wajib diisi saat membatalkan test drive (alasan untuk customer).',
        ];
    }

    private function testDrive(): TestDrive
    {
        return $this->route('testDrive')->loadMissing('car:id,vehicle_condition,stock');
    }

    /**
     * Status setelah disimpan: status baru, atau status sekarang bila tetap.
     */
    private function resultingStatus(): string
    {
        return $this->input('status') ?? $this->testDrive()->status;
    }
}
