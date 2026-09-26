<?php

namespace Tests\Unit;

use App\Reports\AdminReportCsv;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminReportCsvTest extends TestCase
{
    /**
     * @return array<string, array{mixed, int|float|string}>
     */
    public static function cellProvider(): array
    {
        return [
            'formula =' => ['=1+1', "'=1+1"],
            'formula +' => ['+62 812', "'+62 812"],
            'formula -' => ['-2+3', "'-2+3"],
            'formula @' => ['@SUM(A1)', "'@SUM(A1)"],
            'tab' => ["\tHalo", "'\tHalo"],
            'carriage return' => ["\rHalo", "'\rHalo"],
            'teks biasa' => ['Toyota Avanza', 'Toyota Avanza'],
            'karakter formula di tengah' => ['A=B', 'A=B'],
            'teks kosong' => ['', ''],
            'angka bulat negatif tetap angka' => [-5, -5],
            'angka bulat' => [250_000_000, 250_000_000],
            'angka desimal' => [33.3, 33.3],
            'null' => [null, ''],
        ];
    }

    #[DataProvider('cellProvider')]
    public function test_sel_teks_berawalan_formula_diberi_petik(mixed $input, int|float|string $expected): void
    {
        $this->assertSame($expected, AdminReportCsv::cell($input));
    }
}
