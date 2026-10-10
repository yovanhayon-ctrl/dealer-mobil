<?php

namespace App\Console\Commands;

use App\Models\CarImage;
use App\Support\CarImageProcessor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Olah foto mobil lama (sebelum fitur optimasi): kecilkan ke WebP maks. 1600 px dan buat thumbnail.
 * Aman dijalankan ulang: foto yang sudah punya thumbnail dilewati.
 */
#[Signature('cars:optimize-images')]
#[Description('Kecilkan foto mobil lama ke WebP (maks. 1600 px) dan buat thumbnail 480 px.')]
class OptimizeCarImages extends Command
{
    public function handle(CarImageProcessor $processor): int
    {
        $disk = Storage::disk(CarImage::DISK);
        $done = $skipped = $failed = 0;

        CarImage::query()->whereNull('thumb_path')->orderBy('id')->each(function (CarImage $image) use ($processor, $disk, &$done, &$skipped, &$failed) {
            if (! $disk->exists($image->path)) {
                $this->warn("Lewati #{$image->id}: file {$image->path} tidak ditemukan.");
                $skipped++;

                return;
            }

            try {
                $stored = $processor->store($disk->path($image->path), CarImage::directory($image->car_id), CarImage::DISK);
            } catch (RuntimeException $e) {
                $this->warn("Gagal #{$image->id} ({$image->path}): {$e->getMessage()}");
                $failed++;

                return;
            }

            $oldPath = $image->path;
            $image->update($stored);
            $disk->delete($oldPath);
            $done++;
        });

        $this->info("Selesai: {$done} foto dioptimasi, {$skipped} dilewati, {$failed} gagal.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
