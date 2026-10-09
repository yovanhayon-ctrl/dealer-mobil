<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceBooking;
use Database\Seeders\Concerns\SeedsDummyTransactions;
use Illuminate\Database\Seeder;

class ServiceBookingSeeder extends Seeder
{
    use SeedsDummyTransactions;

    /**
     * Booking servis dummy dengan variasi status (hanya local/testing).
     * Aman dijalankan ulang: satu booking per pasangan customer–plat nomor (firstOrCreate),
     * karena tanggal relatif terhadap hari ini berubah setiap hari. Entri yang customer/layanannya
     * tidak ada dilewati.
     */
    public function run(): void
    {
        if (! $this->allowedEnvironment()) {
            return;
        }

        $entries = $this->bookings();
        $customers = $this->dummyCustomers();
        $services = Service::whereIn('slug', array_column($entries, 'service'))->get()->keyBy('slug');

        if ($customers->isEmpty()) {
            $this->command?->warn(static::class.' dilewati: customer dummy @example.test belum ada (isi SEED_CUSTOMER_PASSWORD lalu jalankan CustomerSeeder).');

            return;
        }

        if ($services->isEmpty()) {
            $this->command?->warn(static::class.' dilewati: layanan belum ada (jalankan ServiceSeeder).');

            return;
        }

        $created = 0;

        foreach ($entries as $entry) {
            $customer = $customers->get($entry['email']);
            $service = $services->get($entry['service']);

            if (! $customer || ! $service) {
                continue;
            }

            $booking = ServiceBooking::firstOrCreate(['user_id' => $customer->id, 'plate_number' => $entry['plate']], [
                'service_id' => $service->id,
                'vehicle_model' => $entry['vehicle'],
                'vehicle_year' => $entry['year'],
                'mileage' => $entry['mileage'],
                'preferred_date' => today()->addDays($entry['day'])->toDateString(),
                'preferred_time' => sprintf('%02d:00', $entry['hour']),
                'phone' => $customer->phone ?? '081200000000',
                'complaint' => $entry['complaint'] ?? null,
                'status' => $entry['status'],
                'admin_note' => $entry['admin_note'] ?? null,
            ]);

            $created += (int) $booking->wasRecentlyCreated;
        }

        $this->command?->info("Booking servis dummy siap ({$created} baru).");
    }

    /**
     * Jadwal dikerjakan = hari ini, selesai = masa lalu, sisanya mendatang; setiap slot di bawah kapasitas.
     *
     * @return array<int, array<string, mixed>>
     */
    private function bookings(): array
    {
        return [
            ['email' => 'budi.santoso@example.test', 'service' => 'servis-berkala', 'plate' => 'B 1234 BSA',
                'vehicle' => 'Nissan Grand Livina XV', 'year' => 2018, 'mileage' => 85_000, 'day' => 2, 'hour' => 9,
                'status' => ServiceBooking::STATUS_PENDING, 'complaint' => 'Servis 85.000 km, sekalian cek suara kaki-kaki.'],
            ['email' => 'siti.rahmawati@example.test', 'service' => 'detailing', 'plate' => 'B 2345 SRW',
                'vehicle' => 'Nissan Juke RX', 'year' => 2016, 'mileage' => 92_000, 'day' => 3, 'hour' => 10,
                'status' => ServiceBooking::STATUS_PENDING],
            ['email' => 'andi.pratama@example.test', 'service' => 'inspeksi-kendaraan', 'plate' => 'B 3456 APR',
                'vehicle' => 'Nissan X-Trail 2.5 CVT', 'year' => 2019, 'mileage' => 64_000, 'day' => 4, 'hour' => 13,
                'status' => ServiceBooking::STATUS_CONFIRMED, 'admin_note' => 'Mohon datang 15 menit lebih awal.'],
            ['email' => 'dewi.lestari@example.test', 'service' => 'ban-aki', 'plate' => 'B 4567 DLS',
                'vehicle' => 'Nissan March 1.2 AT', 'year' => 2015, 'mileage' => 110_000, 'day' => 1, 'hour' => 8,
                'status' => ServiceBooking::STATUS_CONFIRMED, 'complaint' => 'Aki sering soak di pagi hari.'],
            ['email' => 'rizky.hidayat@example.test', 'service' => 'perawatan-ringan', 'plate' => 'B 5678 RHD',
                'vehicle' => 'Nissan Serena Highway Star', 'year' => 2017, 'mileage' => 98_000, 'day' => 0, 'hour' => 9,
                'status' => ServiceBooking::STATUS_IN_PROGRESS, 'complaint' => 'AC kurang dingin.'],
            ['email' => 'nur.aisyah@example.test', 'service' => 'servis-berkala', 'plate' => 'B 6789 NAS',
                'vehicle' => 'Nissan Livina VL', 'year' => 2021, 'mileage' => 40_000, 'day' => -7, 'hour' => 10,
                'status' => ServiceBooking::STATUS_COMPLETED, 'admin_note' => 'Oli & filter diganti. Servis berikutnya di 50.000 km.'],
            ['email' => 'agus.setiawan@example.test', 'service' => 'servis-berkala', 'plate' => 'B 7890 AGS',
                'vehicle' => 'Nissan Navara VL', 'year' => 2020, 'mileage' => 70_000, 'day' => -14, 'hour' => 14,
                'status' => ServiceBooking::STATUS_COMPLETED],
            ['email' => 'maya.putri@example.test', 'service' => 'inspeksi-kendaraan', 'plate' => 'B 8901 MPT',
                'vehicle' => 'Nissan Kicks e-Power', 'year' => 2022, 'mileage' => 25_000, 'day' => 5, 'hour' => 11,
                'status' => ServiceBooking::STATUS_CANCELLED, 'admin_note' => 'Customer meminta pembatalan lewat telepon.'],
        ];
    }
}
