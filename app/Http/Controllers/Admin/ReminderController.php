<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SendScheduleReminders;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Tombol "Kirim Pengingat Besok" di dashboard: menjalankan pengingat H-1 (jadwal & batas pembayaran) yang sama dengan scheduler
 * (cadangan tanpa cron).
 */
class ReminderController extends Controller
{
    public function __invoke(SendScheduleReminders $reminders): RedirectResponse
    {
        $sent = $reminders->handle();
        $total = $sent['test_drives'] + $sent['service_bookings'] + $sent['payments'];

        return redirect()->route('admin.dashboard')->with($total > 0 ? 'success' : 'status', $total > 0
            ? "Pengingat terkirim: {$sent['test_drives']} test drive, {$sent['service_bookings']} servis, dan {$sent['payments']} pembayaran"
                .($sent['mechanics'] ? " (juga ke {$sent['mechanics']} mekanik)." : '.')
            : 'Tidak ada jadwal atau batas pembayaran besok yang perlu diingatkan (belum dikonfirmasi atau sudah diingatkan).');
    }
}
