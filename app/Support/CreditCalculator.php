<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Perhitungan kredit bunga flat sesuai config/credit.php (RANCANGAN §5).
 * Dihitung dengan bilangan bulat agar tidak ada selisih pembulatan float.
 */
class CreditCalculator
{
    /** @var array{rates: array<int, int|float>, dp_min: int, dp_max: int, rounding: int} */
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('credit');
    }

    /**
     * @return array<int, int>
     */
    public function tenors(): array
    {
        return array_keys($this->config['rates']);
    }

    public function rate(int $tenor): float
    {
        if (! array_key_exists($tenor, $this->config['rates'])) {
            throw new InvalidArgumentException("Tenor {$tenor} bulan tidak tersedia.");
        }

        return (float) $this->config['rates'][$tenor];
    }

    public function minDownPayment(int $price): int
    {
        return intdiv($price * $this->config['dp_min'] + 99, 100);
    }

    public function maxDownPayment(int $price): int
    {
        return intdiv($price * $this->config['dp_max'], 100);
    }

    /**
     * @return array{price: int, down_payment: int, principal: int, tenor_months: int, interest_rate: float, interest_total: int, monthly_installment: int, total_payment: int}
     */
    public function calculate(int $price, int $downPayment, int $tenor): array
    {
        $rate = $this->rate($tenor);

        if ($price < 1) {
            throw new InvalidArgumentException('Harga harus lebih dari 0.');
        }

        if ($downPayment < $this->minDownPayment($price) || $downPayment > $this->maxDownPayment($price)) {
            throw new InvalidArgumentException(
                "Uang muka harus {$this->config['dp_min']}%–{$this->config['dp_max']}% dari harga."
            );
        }

        $principal = $price - $downPayment;
        // Bunga dalam basis poin (6% = 600) agar 5,5% tetap bilangan bulat.
        $rateBasisPoints = (int) round($rate * 100);
        // Pokok + bunga, dikali 120.000 (= 12 bulan × 10.000 basis poin) supaya tetap bulat.
        $totalScaled = $principal * 120_000 + $principal * $rateBasisPoints * $tenor;
        $rounding = $this->config['rounding'];
        $divisor = 120_000 * $tenor * $rounding;
        $monthly = intdiv($totalScaled + $divisor - 1, $divisor) * $rounding;

        return [
            'price' => $price,
            'down_payment' => $downPayment,
            'principal' => $principal,
            'tenor_months' => $tenor,
            'interest_rate' => $rate,
            'interest_total' => intdiv($principal * $rateBasisPoints * $tenor + 60_000, 120_000),
            'monthly_installment' => $monthly,
            'total_payment' => $downPayment + $monthly * $tenor,
        ];
    }
}
