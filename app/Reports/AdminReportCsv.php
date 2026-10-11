<?php

namespace App\Reports;

/**
 * Menulis AdminReport sebagai CSV (UTF-8 + BOM, pemisah titik koma agar rapi di Excel berbahasa Indonesia).
 * Angka ditulis tanpa titik ribuan supaya tetap bisa dihitung di spreadsheet; desimal memakai koma (33,3).
 */
class AdminReportCsv
{
    private const DELIMITER = ';';

    /** Awalan yang membuat spreadsheet menganggap sel sebagai formula (CSV injection). */
    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public function __construct(private readonly AdminReport $report) {}

    public function filename(): string
    {
        return 'laporan-'.$this->report->period->filenameSuffix().'.csv';
    }

    /**
     * @param  resource  $handle
     * @param  array<int, array<int, mixed>>|null  $rows  baris yang sudah dihitung (default: rows())
     */
    public function write($handle, ?array $rows = null): void
    {
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($rows ?? $this->rows() as $row) {
            fputcsv($handle, array_map(self::cell(...), $row), self::DELIMITER, '"', '');
        }
    }

    /**
     * Teks yang diawali =, +, -, @, tab, atau carriage return diberi awalan petik (')
     * agar tidak dijalankan sebagai formula. Bilangan bulat dibiarkan apa adanya; desimal
     * (persentase, dibulatkan 1 angka) ditulis dengan koma karena Excel Indonesia membacanya begitu.
     */
    public static function cell(mixed $value): int|string
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return number_format($value, 1, ',', '');
        }

        $text = (string) $value;

        return $text !== '' && in_array($text[0], self::FORMULA_PREFIXES, true) ? "'".$text : $text;
    }

    /**
     * Baris CSV: keterangan laporan, lalu tiap bagian (baris kosong, judul, kepala kolom, isi).
     *
     * @return array<int, array<int, mixed>>
     */
    public function rows(): array
    {
        $sections = new AdminReportSections($this->report);
        $rows = $sections->meta();

        foreach ($sections->sections() as $section) {
            $rows[] = [];
            $rows[] = [$section['title']];
            $rows[] = $section['columns'];

            foreach ($section['rows'] as $row) {
                $rows[] = $row['cells'];
            }
        }

        return $rows;
    }
}
