<?php

namespace Tests\Unit;

use App\Support\CreditCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CreditCalculatorTest extends TestCase
{
    private function calculator(): CreditCalculator
    {
        // Memakai file config/credit.php yang sebenarnya.
        return new CreditCalculator(require __DIR__.'/../../config/credit.php');
    }

    public function test_contoh_rancangan_300_juta_dp_60_juta_36_bulan(): void
    {
        $result = $this->calculator()->calculate(300_000_000, 60_000_000, 36);

        $this->assertSame(240_000_000, $result['principal']);
        $this->assertSame(6.0, $result['interest_rate']);
        $this->assertSame(43_200_000, $result['interest_total']);
        $this->assertSame(7_867_000, $result['monthly_installment']);
        $this->assertSame(60_000_000 + 7_867_000 * 36, $result['total_payment']);
    }

    public function test_bunga_per_tenor_sesuai_config(): void
    {
        $calculator = $this->calculator();

        $this->assertSame([12, 24, 36, 48, 60], $calculator->tenors());
        $this->assertSame(5.0, $calculator->rate(12));
        $this->assertSame(5.5, $calculator->rate(24));
        $this->assertSame(7.0, $calculator->rate(60));
    }

    public function test_bunga_pecahan_dan_pembulatan_ke_atas_per_seribu(): void
    {
        // Pokok 100 jt, 5,5% × 2 tahun = 11 jt; (111 jt / 24) = 4.625.000 tepat (tidak dibulatkan).
        $this->assertSame(4_625_000, $this->calculator()->calculate(126_000_000, 26_000_000, 24)['monthly_installment']);

        // Pokok 100.000.001 → cicilan mentah sedikit di atas 4.625.000 → dibulatkan ke atas.
        $this->assertSame(4_626_000, $this->calculator()->calculate(126_000_001, 26_000_000, 24)['monthly_installment']);
    }

    public function test_batas_uang_muka(): void
    {
        $calculator = $this->calculator();

        $this->assertSame(60_000_000, $calculator->minDownPayment(300_000_000));
        $this->assertSame(20, $calculator->minDownPayment(99));
        $this->assertSame(270_000_000, $calculator->maxDownPayment(300_000_000));

        $calculator->calculate(300_000_000, 270_000_000, 12);
        $this->expectException(InvalidArgumentException::class);
        $calculator->calculate(300_000_000, 59_999_999, 12);
    }

    public function test_uang_muka_di_atas_maksimal_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator()->calculate(300_000_000, 270_000_001, 12);
    }

    public function test_tenor_tidak_tersedia_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tenor 18 bulan tidak tersedia.');

        $this->calculator()->calculate(300_000_000, 60_000_000, 18);
    }
}
