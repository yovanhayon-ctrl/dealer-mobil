<?php

namespace App\Http\Requests\Concerns;

/**
 * Nomor HP Indonesia: buang spasi/tanda hubung/titik/kurung, ubah +62/62 menjadi 0.
 */
trait NormalizesPhone
{
    /** 08 + 8–11 digit (total 10–13 digit), awalan operator 081–089. */
    public const PHONE_REGEX = '/^08[1-9][0-9]{7,10}$/';

    private function normalizePhone(mixed $value): string
    {
        $phone = preg_replace('/[\s\-.()]/', '', is_string($value) ? $value : '');

        if (str_starts_with($phone, '+62')) {
            return '0'.substr($phone, 3);
        }

        if (str_starts_with($phone, '62')) {
            return '0'.substr($phone, 2);
        }

        return $phone;
    }
}
