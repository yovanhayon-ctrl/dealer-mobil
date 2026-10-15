<?php

namespace App\Actions;

use App\Models\ServiceBooking;
use App\Notifications\MechanicJobAssigned;
use App\Notifications\Notifier;
use App\Notifications\ServiceBookingStatusChanged;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Ubah status booking servis (admin atau mekanik) di dalam transaksi dengan baris terkunci,
 * catat waktu mulai / selesai, lalu kabari customer bila status benar-benar berubah
 * (dan mekanik yang ditugaskan saat booking dikonfirmasi: pekerjaan baru di Pekerjaan Saya).
 */
class ChangeServiceBookingStatus
{
    /**
     * @param  string|null  $status  null = status tetap (hanya catatan admin)
     * @param  array<string, mixed>  $attributes  kolom lain yang ikut disimpan (mis. admin_note)
     *
     * @throws DomainException transisi tidak diizinkan
     */
    public function handle(ServiceBooking $booking, ?string $status, array $attributes = []): ServiceBooking
    {
        $updated = DB::transaction(function () use ($booking, $status, $attributes) {
            // Baca ulang baris yang dikunci: pengguna lain mungkin sudah mengubah status sejak halaman dibuka.
            $locked = ServiceBooking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            // Status tujuan sudah terpasang (pengguna lain lebih dulu): jangan simpan apa pun.
            if ($status !== null && $status === $locked->status) {
                return $locked;
            }

            if ($status !== null && ! $locked->canTransitionTo($status)) {
                throw new DomainException("Status booking servis tidak bisa diubah dari {$locked->statusLabel()} ke ".ServiceBooking::STATUS_LABELS[$status].'.');
            }

            $locked->fill($attributes);

            if ($status !== null) {
                $locked->status = $status;
                $locked->started_at = $status === ServiceBooking::STATUS_IN_PROGRESS ? now() : $locked->started_at;
                $locked->finished_at = $status === ServiceBooking::STATUS_COMPLETED ? now() : $locked->finished_at;
            }

            $locked->save();

            return $locked;
        });

        // Hanya perubahan status yang benar-benar tersimpan yang dikabarkan ke customer.
        if ($status !== null && $updated->wasChanged('status')) {
            Notifier::send($updated->user, new ServiceBookingStatusChanged($updated));

            if ($status === ServiceBooking::STATUS_CONFIRMED && $updated->mechanic_id !== null) {
                Notifier::send($updated->loadMissing('mechanic')->mechanic, new MechanicJobAssigned($updated));
            }
        }

        return $updated;
    }
}
