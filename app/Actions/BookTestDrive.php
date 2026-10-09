<?php

namespace App\Actions;

use App\Models\Car;
use App\Models\TestDrive;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Booking test drive oleh customer (RANCANGAN §5).
 *
 * Aturan: mobil boleh di-test drive (Car::canBeTestDriven), customer belum punya test drive
 * pending/confirmed untuk mobil yang sama, dan slot (mobil + tanggal + jam) belum dipesan.
 * Aturan dicek di TestDriveRequest (pesan ramah) lalu dicek ulang di sini dengan baris mobil
 * dikunci (lockForUpdate), sehingga dua booking bersamaan tidak lolos keduanya.
 */
class BookTestDrive
{
    /**
     * @param  array{car_id: int|string, preferred_date: string, preferred_time: string, phone: string, notes: ?string}  $data
     *
     * @throws ValidationException jika aturan booking dilanggar
     */
    public function handle(User $user, array $data): TestDrive
    {
        return DB::transaction(function () use ($user, $data) {
            $car = Car::whereKey($data['car_id'])->lockForUpdate()->firstOrFail();

            $errors = $this->conflicts($user, $car, $data['preferred_date'], $data['preferred_time']);

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            return TestDrive::create([
                'user_id' => $user->id,
                'car_id' => $car->id,
                'preferred_date' => $data['preferred_date'],
                'preferred_time' => $data['preferred_time'],
                'phone' => $data['phone'],
                'notes' => $data['notes'] ?: null,
                'status' => TestDrive::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Pelanggaran aturan booking: [field => pesan]; kosong jika boleh.
     *
     * @return array<string, string>
     */
    public function conflicts(User $user, Car $car, string $date, string $time): array
    {
        if (! $car->is_active) {
            return ['car_id' => 'Mobil ini tidak tersedia untuk test drive.'];
        }

        if (! $car->canBeTestDriven()) {
            return ['car_id' => 'Unit mobil bekas ini sudah terjual (stok habis), test drive tidak tersedia.'];
        }

        $existing = TestDrive::query()
            ->active()
            ->where('user_id', $user->id)
            ->where('car_id', $car->id)
            ->orderBy('preferred_date')
            ->first(['id', 'status', 'preferred_date', 'preferred_time']);

        if ($existing) {
            return ['car_id' => "Anda sudah punya jadwal test drive untuk mobil ini ({$existing->statusLabel()}, "
                .$existing->preferred_date->translatedFormat('d M Y')." {$existing->timeLabel()} WIB). "
                .'Lihat Riwayat Test Drive.'];
        }

        $slotTaken = TestDrive::query()
            ->active()
            ->where('car_id', $car->id)
            ->whereDate('preferred_date', $date)
            ->whereTime('preferred_time', '=', "{$time}:00")
            ->exists();

        if ($slotTaken) {
            return ['preferred_time' => 'Jadwal ini sudah dipesan, silakan pilih jam atau tanggal lain.'];
        }

        return [];
    }
}
