<?php

namespace App\Actions;

use App\Models\ServiceBooking;
use App\Models\TestDrive;
use App\Notifications\MechanicJobAssigned;
use App\Notifications\Notifier;
use App\Notifications\ScheduleChanged;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Staf menjadwalkan ulang test drive / booking servis yang masih Menunggu atau Dikonfirmasi (termasuk yang lewat jadwal).
 * Jadwal baru dianggap sudah disepakati dengan customer: status menjadi Dikonfirmasi, catatan berisi jadwal lama → baru,
 * customer (dan mekanik yang ditugaskan) diberi tahu. Bentrok slot dicek ulang dengan baris terkunci.
 */
class RescheduleBooking
{
    /**
     * @throws DomainException jadwal tidak bisa diubah atau bentrok (pesan siap tampil)
     */
    public function handle(TestDrive|ServiceBooking $booking, string $date, string $time): TestDrive|ServiceBooking
    {
        $updated = DB::transaction(function () use ($booking, $date, $time) {
            $locked = $booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (! $locked->canBeRescheduled()) {
                throw new DomainException("Jadwal tidak bisa diubah karena statusnya sudah {$locked->statusLabel()}.");
            }

            if ($message = $this->conflict($locked, $date, $time, lock: true)) {
                throw new DomainException($message);
            }

            $before = $locked->preferred_date->translatedFormat('d M Y')." {$locked->timeLabel()}";
            $locked->preferred_date = $date;
            $locked->preferred_time = $time;
            $locked->status = $locked::STATUS_CONFIRMED;
            $locked->admin_note = "Jadwal diubah dari {$before} ke ".$locked->preferred_date->translatedFormat('d M Y')." {$time} WIB.";
            $locked->save();

            return $locked;
        });

        Notifier::send($updated->loadMissing('user')->user, new ScheduleChanged($updated));

        if ($updated instanceof ServiceBooking && $updated->mechanic_id !== null) {
            Notifier::send($updated->loadMissing('mechanic')->mechanic, new MechanicJobAssigned($updated, 'Jadwal Servis Diubah'));
        }

        return $updated;
    }

    /**
     * Pesan bentrok untuk jadwal baru; null bila aman. Booking itu sendiri tidak dihitung.
     */
    public function conflict(TestDrive|ServiceBooking $booking, string $date, string $time, bool $lock = false): ?string
    {
        if ($booking->preferred_date->toDateString() === $date && $booking->timeLabel() === $time) {
            return 'Jadwal baru sama dengan jadwal sekarang.';
        }

        $sameSlot = fn () => $booking::query()
            ->active()
            ->whereKeyNot($booking->id)
            ->whereDate('preferred_date', $date)
            ->whereTime('preferred_time', '=', "{$time}:00")
            ->when($lock, fn ($query) => $query->lockForUpdate());

        if ($booking instanceof TestDrive) {
            // Query terpisah: relasi car pada model tidak ditimpa kolom terbatas (notifikasi butuh nama & merek).
            $car = $booking->car()->first(['id', 'vehicle_condition', 'stock']);

            if (! $car->isNew() && ! $car->inStock()) {
                return 'Unit mobil bekas ini sudah terjual (stok 0), test drive tidak bisa dijadwalkan ulang.';
            }

            return $sameSlot()->where('car_id', $booking->car_id)->exists()
                ? 'Mobil ini sudah dijadwalkan test drive pada jam itu. Pilih jam atau tanggal lain.'
                : null;
        }

        if ($sameSlot()->get(['id'])->count() >= ServiceBooking::slotCapacity()) {
            return 'Slot bengkel pada jam itu sudah penuh. Pilih jam atau tanggal lain.';
        }

        if ($booking->mechanic_id !== null && $sameSlot()->where('mechanic_id', $booking->mechanic_id)->exists()) {
            return 'Mekanik yang ditugaskan sudah ada jadwal di jam itu. Pilih jam lain atau ganti mekanik dulu.';
        }

        return null;
    }
}
