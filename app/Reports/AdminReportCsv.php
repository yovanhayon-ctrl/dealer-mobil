<?php

namespace App\Reports;

use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\TestDrive;

/**
 * Menulis AdminReport sebagai CSV (UTF-8 + BOM, pemisah titik koma agar rapi di Excel berbahasa Indonesia).
 * Angka ditulis apa adanya (tanpa titik ribuan) supaya tetap bisa dihitung di spreadsheet.
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
     * agar tidak dijalankan sebagai formula. Angka dibiarkan sebagai angka.
     */
    public static function cell(mixed $value): int|float|string
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $text = (string) $value;

        return $text !== '' && in_array($text[0], self::FORMULA_PREFIXES, true) ? "'".$text : $text;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function rows(): array
    {
        $purchases = $this->report->purchaseSummary();
        $testDrives = $this->report->testDriveSummary();

        $rows = [
            ['Laporan', config('dealer.name')],
            ['Periode', $this->report->period->label()],
            ['Dibuat', now()->translatedFormat('j F Y H:i').' WIB'],
            ['Catatan', 'Terjual = pengajuan Disetujui/Selesai; periode pengajuan berdasarkan tanggal pengajuan, test drive berdasarkan tanggal jadwal.'],
            [],
            ['Ringkasan Pengajuan'],
            ['Keterangan', 'Jumlah'],
            ['Total pengajuan', $purchases['total']],
        ];

        foreach ($purchases['statuses'] as $status => $count) {
            $rows[] = ['Status '.PurchaseRequest::STATUS_LABELS[$status], $count];
        }

        array_push($rows,
            ['Cash', $purchases['cash']],
            ['Kredit', $purchases['credit']],
            ['Unit terjual', $purchases['units_sold']],
            ['Nilai penjualan (Rp)', $purchases['sales_value']],
        );

        foreach (['Penjualan per Merek' => ['Merek', $this->report->salesByBrand()], 'Penjualan per Kategori' => ['Kategori', $this->report->salesByCategory()]] as $title => [$column, $items]) {
            $rows[] = [];
            $rows[] = [$title];
            $rows[] = [$column, 'Unit', 'Nilai (Rp)'];

            foreach ($items as $item) {
                $rows[] = [$item->name, $item->units, $item->value];
            }
        }

        $rows[] = [];
        $rows[] = ['5 Mobil Terlaris'];
        $rows[] = ['Peringkat', 'Mobil', 'Unit', 'Nilai (Rp)'];
        foreach ($this->report->topCars()->values() as $index => $car) {
            $rows[] = [$index + 1, $car->name, $car->units, $car->value];
        }

        $rows[] = [];
        $rows[] = ['Test Drive per Status'];
        $rows[] = ['Status', 'Jumlah'];
        foreach ($testDrives['statuses'] as $status => $count) {
            $rows[] = [TestDrive::STATUS_LABELS[$status], $count];
        }
        $rows[] = ['Total test drive', $testDrives['total']];
        $rows[] = ['Persentase selesai (%)', $testDrives['completion_rate']];

        $rows[] = [];
        $rows[] = ['Stok Menipis (mobil aktif, stok maksimal '.Car::LOW_STOCK_THRESHOLD.')'];
        $rows[] = ['Mobil', 'Kondisi', 'Stok'];
        foreach ($this->report->lowStockCars() as $car) {
            $rows[] = ["{$car->brand->name} {$car->name} {$car->year}", $car->condition_label, $car->stock];
        }

        return $rows;
    }
}
