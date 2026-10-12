<?php

namespace App\Http\Requests\Concerns;

use App\Support\NumberInput;

/**
 * Angka boleh ditulis dengan titik ribuan atau awalan "Rp" ("Rp 285.000.000"): diambil digitnya sebelum validasi.
 * Isian berhuruf ("45rb") diteruskan apa adanya sehingga aturan "integer" menolaknya ("… hanya boleh berisi angka.").
 */
trait NormalizesDigits
{
    private function digitsOnly(string $key): ?string
    {
        return NumberInput::normalize($this->input($key));
    }
}
