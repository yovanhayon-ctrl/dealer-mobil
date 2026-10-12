<?php

namespace App\Reports;

use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\ServiceBooking;
use App\Models\TestDrive;

/**
 * Isi laporan admin dalam bentuk bagian-bagian tabel, dipakai bersama oleh export CSV dan Excel.
 *
 * Setiap baris boleh membawa format angka sendiri (mis. "Nilai penjualan" berformat rupiah di kolom Jumlah)
 * dan penanda baris total. Format: text, number, rupiah, percent (nilai persen, mis. 33.3), rank (nomor urut).
 *
 * @phpstan-type Row array{cells: list<mixed>, formats?: list<string>, total?: bool}
 * @phpstan-type Section array{title: string, columns: list<string>, formats: list<string>, rows: list<Row>}
 */
class AdminReportSections
{
    public function __construct(private readonly AdminReport $report) {}

    /**
     * Baris keterangan di atas laporan: [label, isi].
     *
     * @return list<array{0: string, 1: string}>
     */
    public function meta(): array
    {
        return [
            ['Laporan', (string) config('dealer.name')],
            ['Periode', $this->report->period->label()],
            ['Dibuat', now()->translatedFormat('j F Y H:i').' WIB'],
            ['Catatan', 'Terjual = pengajuan Disetujui/Selesai; periode pengajuan berdasarkan tanggal pengajuan, test drive & booking servis berdasarkan tanggal jadwal.'],
        ];
    }

    /**
     * @return list<Section>
     */
    public function sections(): array
    {
        $purchases = $this->report->purchaseSummary();
        $testDrives = $this->report->testDriveSummary();
        $services = $this->report->serviceSummary();

        $summary = [self::row(['Total pengajuan', $purchases['total']], total: true)];
        foreach ($purchases['statuses'] as $status => $count) {
            $summary[] = self::row(['Status '.PurchaseRequest::STATUS_LABELS[$status], $count]);
        }
        array_push($summary,
            self::row(['Cash', $purchases['cash']]),
            self::row(['Kredit', $purchases['credit']]),
            self::row(['Unit terjual', $purchases['units_sold']]),
            self::row(['Nilai penjualan (Rp)', $purchases['sales_value']], ['text', 'rupiah']),
        );

        $sections = [self::section('Ringkasan Pengajuan', ['Keterangan', 'Jumlah'], ['text', 'number'], $summary)];

        foreach (['Penjualan per Merek' => ['Merek', $this->report->salesByBrand()], 'Penjualan per Kategori' => ['Kategori', $this->report->salesByCategory()]] as $title => [$column, $items]) {
            $sections[] = self::section($title, [$column, 'Unit', 'Nilai (Rp)'], ['text', 'number', 'rupiah'],
                $items->map(fn ($item) => self::row([$item->name, $item->units, $item->value]))->values()->all());
        }

        $sections[] = self::section('5 Mobil Terlaris', ['Peringkat', 'Mobil', 'Unit', 'Nilai (Rp)'], ['rank', 'text', 'number', 'rupiah'],
            $this->report->topCars()->values()->map(fn ($car, $index) => self::row([$index + 1, $car->name, $car->units, $car->value]))->all());

        $testDriveRows = [];
        foreach ($testDrives['statuses'] as $status => $count) {
            $testDriveRows[] = self::row([TestDrive::STATUS_LABELS[$status], $count]);
        }
        $testDriveRows[] = self::row(['Total test drive', $testDrives['total']], total: true);
        $testDriveRows[] = self::row(['Persentase selesai (%)', $testDrives['completion_rate']], ['text', 'percent']);
        $sections[] = self::section('Test Drive per Status', ['Status', 'Jumlah'], ['text', 'number'], $testDriveRows);

        $serviceRows = [];
        foreach ($services['statuses'] as $status => $count) {
            $serviceRows[] = self::row([ServiceBooking::STATUS_LABELS[$status], $count]);
        }
        $serviceRows[] = self::row(['Total booking servis', $services['total']], total: true);
        $serviceRows[] = self::row(['Persentase servis selesai (%)', $services['completion_rate']], ['text', 'percent']);
        $sections[] = self::section('Booking Servis per Status', ['Status', 'Jumlah'], ['text', 'number'], $serviceRows);

        $sections[] = self::section('Layanan Servis Terpopuler', ['Layanan', 'Booking', 'Selesai'], ['text', 'number', 'number'],
            $this->report->servicesByPopularity()->map(fn ($service) => self::row([$service->name, $service->total, $service->completed]))->values()->all());

        $sections[] = self::section('Stok Menipis (mobil aktif, stok maksimal '.Car::LOW_STOCK_THRESHOLD.')', ['Mobil', 'Kondisi', 'Stok'], ['text', 'text', 'number'],
            $this->report->lowStockCars()->map(fn ($car) => self::row(["{$car->brand->name} {$car->name} {$car->year}", $car->condition_label, $car->stock]))->values()->all());

        return $sections;
    }

    /**
     * @param  list<mixed>  $cells
     * @param  list<string>|null  $formats
     * @return array{cells: list<mixed>, formats?: list<string>, total?: bool}
     */
    private static function row(array $cells, ?array $formats = null, bool $total = false): array
    {
        return array_filter(['cells' => $cells, 'formats' => $formats, 'total' => $total ?: null], fn ($value) => $value !== null);
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $formats
     * @param  list<array{cells: list<mixed>, formats?: list<string>, total?: bool}>  $rows
     * @return array{title: string, columns: list<string>, formats: list<string>, rows: list<array{cells: list<mixed>, formats?: list<string>, total?: bool}>}
     */
    private static function section(string $title, array $columns, array $formats, array $rows): array
    {
        return ['title' => $title, 'columns' => $columns, 'formats' => $formats, 'rows' => $rows];
    }
}
