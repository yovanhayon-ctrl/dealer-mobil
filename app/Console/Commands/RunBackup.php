<?php

namespace App\Console\Commands;

use App\Actions\CreateBackup;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Number;
use RuntimeException;
use Throwable;

/**
 * Cadangan database + foto + bukti pembayaran ke satu zip (lihat CreateBackup). Dijalankan scheduler setiap hari
 * (routes/console.php, DEALER_BACKUP_TIME) atau manual sebelum memperbarui aplikasi: php artisan backup:run
 */
#[Signature('backup:run')]
#[Description('Buat cadangan database, foto, dan bukti pembayaran (zip), lalu hapus cadangan lama')]
class RunBackup extends Command
{
    public function handle(CreateBackup $backup): int
    {
        try {
            $result = $backup->handle();
        } catch (RuntimeException $e) {
            report($e);
            $this->error('Cadangan gagal: '.$e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            report($e);
            $this->error('Cadangan gagal, lihat storage/logs untuk detail.');

            return self::FAILURE;
        }

        $deleted = $backup->prune((int) config('dealer.backup.keep_days'));

        $this->info('Cadangan dibuat: '.$result['path']);
        $this->line("{$result['tables']} tabel, {$result['files']} file, ".Number::fileSize($result['size'], 1).'.'
            .($deleted ? " {$deleted} cadangan lama dihapus." : ''));

        return self::SUCCESS;
    }
}
