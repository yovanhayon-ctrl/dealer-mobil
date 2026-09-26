<?php

namespace App\Http\Requests\Admin;

use App\Reports\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportRequest extends FormRequest
{
    /**
     * Akses sudah dibatasi middleware auth + admin pada grup route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Tanpa parameter = bulan ini; tanggal diisi tanpa `periode` = kustom.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('periode')) {
            return;
        }

        $this->merge([
            'periode' => $this->filled('dari') || $this->filled('sampai') ? ReportPeriod::CUSTOM : ReportPeriod::THIS_MONTH,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isCustom = $this->input('periode') === ReportPeriod::CUSTOM;

        return [
            'periode' => ['required', Rule::in([...array_keys(ReportPeriod::PRESETS), ReportPeriod::CUSTOM])],
            'dari' => $isCustom ? ['required', 'date_format:Y-m-d'] : ['nullable'],
            'sampai' => $isCustom ? ['required', 'date_format:Y-m-d', 'after_or_equal:dari'] : ['nullable'],
        ];
    }

    /**
     * Rentang kustom maksimal 1 tahun (mis. 26 Sep 2025 – 25 Sep 2026).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('periode') !== ReportPeriod::CUSTOM || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $from = CarbonImmutable::createFromFormat('!Y-m-d', $this->input('dari'));
                $to = CarbonImmutable::createFromFormat('!Y-m-d', $this->input('sampai'));

                if ($to->greaterThan($from->addYearNoOverflow()->subDay())) {
                    $validator->errors()->add('sampai', 'Rentang laporan maksimal 1 tahun.');
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
            'periode' => 'periode',
            'dari' => 'tanggal dari',
            'sampai' => 'tanggal sampai',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periode.in' => 'Pilihan periode tidak dikenal.',
            'dari.required' => 'Tanggal dari wajib diisi.',
            'sampai.required' => 'Tanggal sampai wajib diisi.',
            'dari.date_format' => 'Tanggal dari tidak valid.',
            'sampai.date_format' => 'Tanggal sampai tidak valid.',
            'sampai.after_or_equal' => 'Tanggal sampai tidak boleh sebelum tanggal dari.',
        ];
    }

    public function period(): ReportPeriod
    {
        $preset = $this->validated('periode');

        return $preset === ReportPeriod::CUSTOM
            ? ReportPeriod::custom($this->validated('dari'), $this->validated('sampai'))
            : ReportPeriod::preset($preset);
    }

    /**
     * GET yang gagal validasi kembali ke laporan bulan ini (bukan ke URL sebelumnya yang bisa berasal dari luar).
     */
    protected function getRedirectUrl(): string
    {
        return route('admin.reports.index');
    }
}
