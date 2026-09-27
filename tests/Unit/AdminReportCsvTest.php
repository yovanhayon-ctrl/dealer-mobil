<?php

namespace Tests\Unit;

use App\Reports\AdminReportCsv;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdminReportCsvTest extends TestCase
{
    /**
     * @return array<string, array{mixed, int|string}>
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
            'angka desimal memakai koma' => [33.3, '33,3'],
            'angka desimal bulat' => [50.0, '50,0'],
            'angka desimal negatif tidak diberi petik' => [-2.5, '-2,5'],
            'angka desimal besar tanpa pemisah ribuan' => [1234.5, '1234,5'],
            'null' => [null, ''],
        ];
    }

    #[DataProvider('cellProvider')]
    public function test_sel_teks_berawalan_formula_diberi_petik(mixed $input, int|string $expected): void
    {
        $this->assertSame($expected, AdminReportCsv::cell($input));
    }
}
