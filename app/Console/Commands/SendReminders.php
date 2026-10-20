<?php

namespace App\Console\Commands;

use App\Actions\SendScheduleReminders;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Dijalankan scheduler setiap hari (routes/console.php, DEALER_REMINDER_TIME); juga lewat tombol dashboard atau manual.
 * Contoh: php artisan reminders:send --dry-run
 */
#[Signature('reminders:send {--dry-run : Hanya tampilkan jadwal yang akan diingatkan, tanpa mengirim}')]
#[Description('Kirim pengingat H-1 untuk test drive & booking servis yang dikonfirmasi besok')]
class SendReminders extends Command
{
    public function handle(SendScheduleReminders $reminders): int
    {
        if ($this->option('dry-run')) {
            $due = $reminders->due();

            foreach ($due['test_drives'] as $testDrive) {
                $this->line("Test drive #{$testDrive->id} {$testDrive->timeLabel()} · {$testDrive->user->name} · {$testDrive->car->name}");
            }
            foreach ($due['service_bookings'] as $booking) {
                $mechanic = $booking->mechanic ? " · mekanik {$booking->mechanic->name}" : '';
                $this->line("Servis #{$booking->id} {$booking->timeLabel()} · {$booking->user->name} · {$booking->plate_number}{$mechanic}");
            }
            $this->info("Akan diingatkan: {$due['test_drives']->count()} test drive, {$due['service_bookings']->count()} servis (belum dikirim).");

            return self::SUCCESS;
        }

        $sent = $reminders->handle();
        $this->info("Pengingat terkirim: {$sent['test_drives']} test drive, {$sent['service_bookings']} servis, {$sent['mechanics']} ke mekanik.");

        return self::SUCCESS;
    }
}
