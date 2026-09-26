<?php

namespace App\Http\Requests\Concerns;

/**
 * Angka boleh ditulis dengan titik ribuan ("285.000.000"): ambil digitnya saja sebelum validasi.
 */
trait NormalizesDigits
{
    private function digitsOnly(string $key): ?string
    {
        $value = preg_replace('/\D/', '', (string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
