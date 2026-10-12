<?php

namespace App\Support;

/**
 * Isian angka dari pengguna (harga, kilometer, DP, tahun, durasi).
 *
 * Diterima: angka dengan titik/spasi pemisah ribuan dan awalan "Rp" opsional ("Rp 285.000.000", "45 000").
 * Ditolak: huruf atau simbol lain ("45rb", "1,2 M", "abc"); tidak dibuang diam-diam agar "45rb" tidak tersimpan sebagai 45.
 */
final class NumberInput
{
    private const PATTERN = '/^(?:rp\.?\s*)?\d[\d.\s]*$/i';

    public static function isValid(string $value): bool
    {
        return preg_match(self::PATTERN, trim($value)) === 1;
    }

    /**
     * Digit saja ("Rp 285.000.000" → "285000000"); null bila kosong. Nilai tidak valid dikembalikan apa adanya
     * supaya aturan validasi "integer" menolaknya dengan pesan yang jelas.
     */
    public static function normalize(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        return self::isValid($text) ? (string) preg_replace('/\D/', '', $text) : $text;
    }
}
