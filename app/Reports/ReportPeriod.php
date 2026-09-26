<?php

namespace App\Reports;

use Carbon\CarbonImmutable;

/**
 * Periode laporan (hari penuh WIB, sesuai timezone aplikasi).
 */
final class ReportPeriod
{
    public const THIS_MONTH = 'bulan-ini';

    public const LAST_MONTH = 'bulan-lalu';

    public const THIS_YEAR = 'tahun-ini';

    public const CUSTOM = 'kustom';

    /** Nilai `periode` di query string => label tombol. */
    public const PRESETS = [
        self::THIS_MONTH => 'Bulan ini',
        self::LAST_MONTH => 'Bulan lalu',
        self::THIS_YEAR => 'Tahun ini',
    ];

    public readonly CarbonImmutable $from;

    public readonly CarbonImmutable $to;

    public function __construct(CarbonImmutable $from, CarbonImmutable $to, public readonly string $preset = self::CUSTOM)
    {
        $this->from = $from->startOfDay();
        $this->to = $to->endOfDay();
    }

    public static function preset(string $preset): self
    {
        $today = CarbonImmutable::today();

        return match ($preset) {
            self::LAST_MONTH => new self($today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth(), $preset),
            self::THIS_YEAR => new self($today->startOfYear(), $today->endOfYear(), $preset),
            default => new self($today->startOfMonth(), $today->endOfMonth(), self::THIS_MONTH),
        };
    }

    public static function custom(string $from, string $to): self
    {
        return new self(CarbonImmutable::createFromFormat('!Y-m-d', $from), CarbonImmutable::createFromFormat('!Y-m-d', $to));
    }

    /**
     * Mis. "1 September 2026 – 30 September 2026".
     */
    public function label(): string
    {
        return $this->from->translatedFormat('j F Y').' – '.$this->to->translatedFormat('j F Y');
    }

    /**
     * Mis. "2026-09-01_2026-09-30" (untuk nama file export).
     */
    public function filenameSuffix(): string
    {
        return $this->from->toDateString().'_'.$this->to->toDateString();
    }

    /**
     * Query string untuk link export/cetak dengan periode yang sama.
     *
     * @return array<string, string>
     */
    public function query(): array
    {
        return $this->preset === self::CUSTOM
            ? ['periode' => self::CUSTOM, 'dari' => $this->from->toDateString(), 'sampai' => $this->to->toDateString()]
            : ['periode' => $this->preset];
    }
}
