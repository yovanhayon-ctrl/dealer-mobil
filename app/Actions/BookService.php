<?php

namespace App\Actions;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Booking servis oleh customer (JAF Service).
 *
 * Aturan: layanan aktif, satu plat nomor hanya punya satu booking aktif, dan jumlah booking aktif
 * per slot (tanggal + jam) tidak melebihi kapasitas bengkel (config dealer.service_slot_capacity).
 * Aturan dicek di ServiceBookingRequest (pesan ramah) lalu dicek ulang di sini dalam transaksi:
 * baris layanan dikunci dan pembacaan slot/plat memakai lockForUpdate, sehingga dua booking
 * bersamaan tidak lolos keduanya.
 */
class BookService
{
    /**
     * @param  array{service_id: int|string, vehicle_model: string, plate_number: string, vehicle_year: ?int, mileage: ?int, preferred_date: string, preferred_time: string, phone: string, complaint: ?string}  $data
     *
     * @throws ValidationException jika aturan booking dilanggar
     */
    public function handle(User $user, array $data): ServiceBooking
    {
        return DB::transaction(function () use ($user, $data) {
            $service = Service::whereKey($data['service_id'])->lockForUpdate()->firstOrFail();

            $errors = $this->conflicts($user, $service, $data['plate_number'], $data['preferred_date'], $data['preferred_time'], lock: true);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            return ServiceBooking::create([
                'user_id' => $user->id,
                'service_id' => $service->id,
                'vehicle_model' => $data['vehicle_model'],
                'plate_number' => $data['plate_number'],
                'vehicle_year' => $data['vehicle_year'] ?? null,
                'mileage' => $data['mileage'] ?? null,
                'preferred_date' => $data['preferred_date'],
                'preferred_time' => $data['preferred_time'],
                'phone' => $data['phone'],
                'complaint' => ($data['complaint'] ?? null) ?: null,
                'status' => ServiceBooking::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Pelanggaran aturan booking: [field => pesan]; kosong jika boleh.
     *
     * @return array<string, string>
     */
    public function conflicts(User $user, Service $service, string $plate, string $date, string $time, bool $lock = false): array
    {
        if (! $service->is_active) {
            return ['service_id' => 'Layanan ini sedang tidak tersedia. Silakan pilih layanan lain.'];
        }

        $existing = ServiceBooking::query()
            ->active()
            ->where('plate_number', $plate)
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->orderBy('preferred_date')
            ->first(['id', 'user_id', 'status', 'preferred_date', 'preferred_time']);

        if ($existing) {
            // Detail jadwal hanya ditampilkan kepada pemilik booking.
            return ['plate_number' => $existing->user_id === $user->id
                ? "Kendaraan {$plate} sudah punya booking servis aktif ({$existing->statusLabel()}, "
                    .$existing->preferred_date->translatedFormat('d M Y')." {$existing->timeLabel()} WIB). Lihat Riwayat Servis."
                : "Kendaraan {$plate} sudah punya booking servis aktif. Hubungi dealer jika ini kendaraan Anda."];
        }

        $booked = ServiceBooking::query()
            ->active()
            ->whereDate('preferred_date', $date)
            ->whereTime('preferred_time', '=', "{$time}:00")
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->get(['id'])
            ->count();

        if ($booked >= ServiceBooking::slotCapacity()) {
            return ['preferred_time' => 'Slot bengkel pada jam ini sudah penuh, silakan pilih jam atau tanggal lain.'];
        }

        return [];
    }
}
