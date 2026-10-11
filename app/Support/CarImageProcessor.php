<?php

namespace App\Support;

use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Olah foto mobil dengan GD (tanpa library tambahan):
 * - putar sesuai orientasi EXIF (foto HP), lalu kecilkan ke sisi terpanjang maks. 1600 px;
 * - simpan sebagai WebP (latar transparan tetap terjaga); metadata EXIF, termasuk lokasi GPS, ikut terbuang;
 * - buat thumbnail lebar 480 px di subfolder "thumbs" untuk kartu & daftar.
 */
class CarImageProcessor
{
    public const MAX_SIZE = 1600;

    public const THUMB_WIDTH = 480;

    public const QUALITY = 80;

    /**
     * @param  string  $sourceFile  path file lokal (upload sementara atau file lama di disk)
     * @param  string  $directory  folder tujuan di disk, mis. "cars/12"
     * @return array{path: string, thumb_path: string}
     */
    public function store(string $sourceFile, string $directory, string $disk = 'public'): array
    {
        $image = $this->load($sourceFile);

        try {
            $name = Str::random(40).'.webp';
            $path = "{$directory}/{$name}";
            $thumbPath = "{$directory}/thumbs/{$name}";

            $large = $this->resize($image, self::MAX_SIZE, self::MAX_SIZE);
            $thumb = $this->resize($image, self::THUMB_WIDTH, PHP_INT_MAX);

            $storage = Storage::disk($disk);
            $storage->put($path, $this->encode($large));
            $storage->put($thumbPath, $this->encode($thumb));

            return ['path' => $path, 'thumb_path' => $thumbPath];
        } finally {
            unset($image, $large, $thumb);
        }
    }

    private function load(string $file): GdImage
    {
        $contents = @file_get_contents($file);
        $image = $contents === false ? false : @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            throw new RuntimeException('Gambar tidak bisa dibaca.');
        }

        // Foto HP sering disimpan miring + tag Orientation; GD tidak memutarnya otomatis.
        if (function_exists('exif_read_data') && str_starts_with($contents, "\xFF\xD8")) {
            $orientation = (int) (@exif_read_data($file)['Orientation'] ?? 1);
            $angle = match ($orientation) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };

            if ($angle !== 0) {
                $rotated = imagerotate($image, $angle, 0);
                $image = $rotated instanceof GdImage ? $rotated : $image;
            }
        }

        return $image;
    }

    /**
     * Perkecil (tidak pernah memperbesar) agar muat dalam $maxWidth × $maxHeight.
     */
    private function resize(GdImage $image, int $maxWidth, int $maxHeight): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = min(1, $maxWidth / $width, $maxHeight / $height);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $canvas;
    }

    private function encode(GdImage $image): string
    {
        ob_start();
        $ok = imagewebp($image, null, self::QUALITY);
        $data = (string) ob_get_clean();

        if (! $ok || $data === '') {
            throw new RuntimeException('Gagal menyimpan gambar sebagai WebP.');
        }

        return $data;
    }
}
