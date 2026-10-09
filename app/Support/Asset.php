<?php

namespace App\Support;

/**
 * URL aset lokal dengan nomor versi otomatis (waktu ubah file), mis. /css/app.css?v=1791455000.
 * Browser boleh menyimpan CSS/JS lama-lama (lihat public/.htaccess); begitu file berubah,
 * URL-nya ikut berubah sehingga versi baru langsung dipakai.
 */
class Asset
{
    public static function url(string $path): string
    {
        $file = public_path($path);
        $url = asset($path);

        return is_file($file) ? $url.'?v='.filemtime($file) : $url;
    }
}
