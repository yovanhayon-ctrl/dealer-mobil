<?php

namespace App\Actions;

use App\Support\DatabaseDumper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

/**
 * Cadangan harian: satu zip berisi database (database.sql), foto & banner (storage/app/public),
 * dan bukti pembayaran (storage/app/private). Disimpan di DEALER_BACKUP_PATH (bawaan storage/app/backups, tidak bisa
 * dibuka dari browser); cadangan yang lebih tua dari DEALER_BACKUP_KEEP_DAYS hari dihapus. File .env tidak ikut
 * (berisi kata sandi): simpan salinannya sendiri di tempat aman.
 */
class CreateBackup
{
    public const PREFIX = 'cadangan-';

    /** Dashboard memperingatkan bila cadangan terakhir lebih tua dari ini. */
    public const STALE_AFTER_HOURS = 48;

    public function __construct(private DatabaseDumper $dumper) {}

    /**
     * @return array{path: string, size: int, tables: int, files: int}
     */
    public function handle(): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP zip belum aktif di hosting. Aktifkan lewat cPanel > Select PHP Version.');
        }

        $directory = self::directory();
        File::ensureDirectoryExists($directory, 0750);

        $path = $directory.DIRECTORY_SEPARATOR.self::PREFIX.now()->format('Y-m-d-His').'.zip';
        $sql = tempnam(sys_get_temp_dir(), 'dump');
        $zip = new ZipArchive;

        try {
            $tables = $this->dumper->dump($sql);

            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("File cadangan {$path} tidak bisa dibuat.");
            }

            $zip->addFile($sql, 'database.sql');
            $files = $this->addDirectory($zip, Storage::disk('public')->path(''), 'storage/app/public')
                + $this->addDirectory($zip, Storage::disk('local')->path(''), 'storage/app/private');

            if (! $zip->close()) {
                throw new RuntimeException("File cadangan {$path} gagal disimpan.");
            }
        } catch (RuntimeException $e) {
            @unlink($path);

            throw $e;
        } finally {
            @unlink($sql);
        }

        return ['path' => $path, 'size' => (int) filesize($path), 'tables' => $tables, 'files' => $files];
    }

    /**
     * Hapus cadangan yang lebih tua dari $keepDays hari (hanya file berawalan "cadangan-"), cadangan terbaru selalu disimpan.
     *
     * @return int jumlah file yang dihapus
     */
    public function prune(int $keepDays): int
    {
        $limit = now()->subDays($keepDays)->getTimestamp();
        $deleted = 0;

        foreach (array_slice(self::files(), 1) as $file) {
            if ($file->getMTime() < $limit && @unlink($file->getPathname())) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Cadangan terbaru (untuk dashboard), null bila belum ada.
     *
     * @return array{name: string, size: int, created_at: Carbon}|null
     */
    public static function latest(): ?array
    {
        $file = self::files()[0] ?? null;

        return $file ? [
            'name' => $file->getFilename(),
            'size' => $file->getSize(),
            'created_at' => Carbon::createFromTimestamp($file->getMTime(), config('app.timezone')),
        ] : null;
    }

    public static function directory(): string
    {
        return rtrim((string) config('dealer.backup.path'), '\\/');
    }

    /**
     * @return list<SplFileInfo> terbaru lebih dulu
     */
    private static function files(): array
    {
        $files = glob(self::directory().DIRECTORY_SEPARATOR.self::PREFIX.'*.zip') ?: [];
        $files = array_map(fn ($path) => new SplFileInfo($path), $files);
        usort($files, fn (SplFileInfo $a, SplFileInfo $b) => [$b->getMTime(), $b->getFilename()] <=> [$a->getMTime(), $a->getFilename()]);

        return $files;
    }

    /**
     * @return int jumlah file yang ditambahkan
     */
    private function addDirectory(ZipArchive $zip, string $root, string $prefix): int
    {
        $root = rtrim($root, '\\/');

        if (! is_dir($root)) {
            return 0;
        }

        $count = 0;
        $exclude = realpath(self::directory()) ?: null;
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($files as $file) {
            $real = $file->getRealPath();

            // Jangan ikutkan folder cadangan itu sendiri bila DEALER_BACKUP_PATH diarahkan ke dalam storage/app.
            if (! $file->isFile() || $real === false || ($exclude && str_starts_with($real, $exclude)) || $file->getFilename() === '.gitignore') {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
            $zip->addFile($file->getPathname(), "{$prefix}/{$relative}");
            $count++;
        }

        return $count;
    }
}
