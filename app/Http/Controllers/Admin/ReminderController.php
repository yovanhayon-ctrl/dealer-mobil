<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SendScheduleReminders;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Tombol "Kirim Pengingat Besok" di dashboard: menjalankan pengingat H-1 yang sama dengan scheduler (cadangan tanpa cron).
 */
class ReminderController extends Controller
{
    public function __invoke(SendScheduleReminders $reminders): RedirectResponse
    {
        $sent = $reminders->handle();
        $total = $sent['test_drives'] + $sent['service_bookings'];

        return redirect()->route('admin.dashboard')->with($total > 0 ? 'success' : 'status', $total > 0
            ? "Pengingat terkirim: {$sent['test_drives']} test drive dan {$sent['service_bookings']} servis".($sent['mechanics'] ? " (juga ke {$sent['mechanics']} mekanik)." : '.')
            : 'Tidak ada jadwal besok yang perlu diingatkan (belum dikonfirmasi atau sudah diingatkan).');
    }
}
