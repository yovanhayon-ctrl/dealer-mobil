<?php

namespace App\Reports;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Menulis AdminReport sebagai file Excel (.xlsx) yang langsung rapi: judul, bagian berlatar hitam JAF,
 * kepala kolom tebal, lebar kolom tetap, angka berformat ribuan/rupiah/persen (tetap angka, bisa dihitung),
 * dan siap cetak A4. Teks ditulis sebagai teks biasa, jadi tidak pernah dijalankan sebagai formula.
 */
class AdminReportXlsx
{
    /** Kolom A-D: label/mobil, angka atau nama mobil (5 terlaris), angka/nilai rupiah, nilai rupiah. */
    private const COLUMN_WIDTHS = ['A' => 40, 'B' => 32, 'C' => 22, 'D' => 22];

    private const LAST_COLUMN = 'D';

    private const NUMBER_FORMATS = [
        'number' => '#,##0',
        'rupiah' => '"Rp" #,##0',
        'percent' => '0.0%',
        'rank' => '0',
    ];

    private const BRAND_DARK = '1C1C1E';

    private const HEADER_FILL = 'F1F3F5';

    public function __construct(private readonly AdminReport $report) {}

    public function filename(): string
    {
        return 'laporan-'.$this->report->period->filenameSuffix().'.xlsx';
    }

    public function spreadsheet(): Spreadsheet
    {
        $sections = new AdminReportSections($this->report);
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Laporan '.config('dealer.name'))
            ->setCreator((string) config('dealer.name'))
            ->setDescription($this->report->period->label());

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan');
        $sheet->setShowGridlines(false);

        foreach (self::COLUMN_WIDTHS as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $row = $this->writeHeader($sheet, $sections->meta());

        foreach ($sections->sections() as $section) {
            $row = $this->writeSection($sheet, $section, $row + 1);
        }

        $this->setupPrint($sheet);
        $sheet->setSelectedCell('A1');

        return $spreadsheet;
    }

    /**
     * @param  resource|string  $target  path file atau "php://output"
     */
    public function save($target, ?Spreadsheet $spreadsheet = null): void
    {
        (new Xlsx($spreadsheet ?? $this->spreadsheet()))->save($target);
    }

    /**
     * Judul besar + baris Periode, Dibuat, Catatan. Mengembalikan baris terakhir yang terisi.
     *
     * @param  list<array{0: string, 1: string}>  $meta
     */
    private function writeHeader(Worksheet $sheet, array $meta): int
    {
        $this->text($sheet, 'A1', 'Laporan '.config('dealer.name'));
        $sheet->mergeCells('A1:'.self::LAST_COLUMN.'1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getRowDimension(1)->setRowHeight(26);

        $row = 2;
        foreach (array_slice($meta, 1) as [$label, $value]) {
            $this->text($sheet, "A{$row}", $label);
            $this->text($sheet, "B{$row}", $value);
            $sheet->mergeCells("B{$row}:".self::LAST_COLUMN.$row);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->getColor()->setRGB('6C757D');
            $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
            $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);

            // Sel gabungan tidak menghitung tinggi otomatis: catatan panjang diberi ruang 2 baris.
            if (mb_strlen($value) > 70) {
                $sheet->getRowDimension($row)->setRowHeight(32);
            }

            $row++;
        }

        return $row - 1;
    }

    /**
     * @param  array{title: string, columns: list<string>, formats: list<string>, rows: list<array{cells: list<mixed>, formats?: list<string>, total?: bool}>}  $section
     */
    private function writeSection(Worksheet $sheet, array $section, int $row): int
    {
        $row++; // satu baris kosong sebelum tiap bagian

        $this->text($sheet, "A{$row}", $section['title']);
        $sheet->mergeCells("A{$row}:".self::LAST_COLUMN.$row);
        $titleStyle = $sheet->getStyle("A{$row}:".self::LAST_COLUMN.$row);
        $titleStyle->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
        $titleStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::BRAND_DARK);
        $sheet->getRowDimension($row)->setRowHeight(20);

        $row++;
        foreach ($section['columns'] as $index => $label) {
            $cell = $this->column($index).$row;
            $this->text($sheet, $cell, $label);
            $this->alignForFormat($sheet, $cell, $section['formats'][$index] ?? 'text');
        }
        $headerStyle = $sheet->getStyle("A{$row}:".self::LAST_COLUMN.$row);
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_FILL);
        $headerStyle->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('ADB5BD');

        if ($section['rows'] === []) {
            $row++;
            $this->text($sheet, "A{$row}", 'Belum ada data pada periode ini.');
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB('6C757D');

            return $row;
        }

        foreach ($section['rows'] as $line) {
            $row++;
            $formats = $line['formats'] ?? $section['formats'];

            foreach ($line['cells'] as $index => $value) {
                $this->value($sheet, $this->column($index).$row, $value, $formats[$index] ?? 'text');
            }

            if ($line['total'] ?? false) {
                $style = $sheet->getStyle("A{$row}:".self::LAST_COLUMN.$row);
                $style->getFont()->setBold(true);
                $style->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('ADB5BD');
            }
        }

        return $row;
    }

    private function value(Worksheet $sheet, string $cell, mixed $value, string $format): void
    {
        if ($format === 'text' || ! is_numeric($value)) {
            $this->text($sheet, $cell, (string) $value);

            return;
        }

        // Persen disimpan sebagai pecahan (33,3% = 0.333) agar format % Excel benar dan tetap bisa dihitung.
        $sheet->setCellValueExplicit($cell, $format === 'percent' ? $value / 100 : $value, DataType::TYPE_NUMERIC);
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(self::NUMBER_FORMATS[$format] ?? self::NUMBER_FORMATS['number']);
        $this->alignForFormat($sheet, $cell, $format);
    }

    /** Teks selalu sebagai string: "=..." tidak pernah menjadi formula. */
    private function text(Worksheet $sheet, string $cell, string $value): void
    {
        $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
    }

    private function alignForFormat(Worksheet $sheet, string $cell, string $format): void
    {
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(
            // Teks & peringkat rata kiri (peringkat di kolom lebar), angka lain rata kanan.
            in_array($format, ['text', 'rank'], true) ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_RIGHT
        );
    }

    private function column(int $index): string
    {
        return chr(ord('A') + $index);
    }

    private function setupPrint(Worksheet $sheet): void
    {
        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true);
        $sheet->getPageMargins()->setTop(0.6)->setBottom(0.6)->setLeft(0.5)->setRight(0.5);
        // Judul laporan diulang di atas setiap halaman cetak.
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
        // "&" adalah kode khusus di header/footer Excel, jadi ditulis ganda.
        $sheet->getHeaderFooter()->setOddFooter('&L'.str_replace('&', '&&', (string) config('dealer.name')).'&RHalaman &P dari &N');
    }
}
