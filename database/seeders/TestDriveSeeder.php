<?php

namespace Database\Seeders;

use App\Models\TestDrive;
use Database\Seeders\Concerns\SeedsDummyTransactions;
use Illuminate\Database\Seeder;

class TestDriveSeeder extends Seeder
{
    use SeedsDummyTransactions;

    /**
     * Test drive dummy dengan variasi status (hanya local/testing).
     * Aman dijalankan ulang: satu test drive per pasangan customer–mobil (firstOrCreate),
     * karena tanggal relatif terhadap hari ini berubah setiap hari. Entri yang customer/mobilnya
     * tidak ada dilewati.
     */
    public function run(): void
    {
        if (! $this->allowedEnvironment()) {
            return;
        }

        $entries = $this->testDrives();
        $customers = $this->dummyCustomers();
        $cars = $this->carsBySlug(array_column($entries, 'car'));

        if (! $this->hasPrerequisites($customers, $cars)) {
            return;
        }

        $created = 0;

        foreach ($entries as $entry) {
            $customer = $customers->get($entry['email']);
            $car = $cars->get($entry['car']);

            if (! $customer || ! $car) {
                continue;
            }

            $testDrive = TestDrive::firstOrCreate(['user_id' => $customer->id, 'car_id' => $car->id], [
                'preferred_date' => today()->addDays($entry['day'])->toDateString(),
                'preferred_time' => sprintf('%02d:00', $entry['hour']),
                'phone' => $customer->phone ?? '081200000000',
                'notes' => $entry['notes'] ?? null,
                'status' => $entry['status'],
                'admin_note' => $entry['admin_note'] ?? null,
            ]);

            $created += (int) $testDrive->wasRecentlyCreated;
        }

        $this->command?->info("Test drive dummy siap ({$created} baru).");
    }

    /**
     * Mobil bekas stok 0 (Civic) hanya dipakai untuk test drive yang dibatalkan;
     * mobil baru stok 0 (Fortuner) boleh dikonfirmasi (unit display).
     *
     * @return array<int, array<string, mixed>>
     */
    private function testDrives(): array
    {
        return [
            ['email' => 'budi.santoso@example.test', 'car' => 'toyota-avanza-1-5-g-cvt-2025', 'day' => 2, 'hour' => 10,
                'status' => TestDrive::STATUS_PENDING, 'notes' => 'Ingin mencoba di jalan tol.'],
            ['email' => 'siti.rahmawati@example.test', 'car' => 'honda-hr-v-1-5-se-cvt-2025', 'day' => 3, 'hour' => 13,
                'status' => TestDrive::STATUS_PENDING],
            ['email' => 'rizky.hidayat@example.test', 'car' => 'mitsubishi-pajero-sport-dakar-4x2-2021', 'day' => 5, 'hour' => 11,
                'status' => TestDrive::STATUS_PENDING, 'notes' => 'Mohon cek kondisi kaki-kaki.'],
            ['email' => 'andi.pratama@example.test', 'car' => 'toyota-fortuner-2-8-gr-sport-2025', 'day' => 4, 'hour' => 9,
                'status' => TestDrive::STATUS_CONFIRMED, 'admin_note' => 'Memakai unit display di showroom.'],
            ['email' => 'dewi.lestari@example.test', 'car' => 'mitsubishi-xpander-cross-premium-cvt-2025', 'day' => 1, 'hour' => 14,
                'status' => TestDrive::STATUS_CONFIRMED],
            ['email' => 'siti.rahmawati@example.test', 'car' => 'daihatsu-xenia-1-3-r-cvt-2025', 'day' => 0, 'hour' => 16,
                'status' => TestDrive::STATUS_CONFIRMED],
            ['email' => 'nur.aisyah@example.test', 'car' => 'hyundai-creta-1-5-prime-ivt-2025', 'day' => -5, 'hour' => 10,
                'status' => TestDrive::STATUS_COMPLETED, 'admin_note' => 'Customer tertarik, lanjut ke pengajuan.'],
            ['email' => 'agus.setiawan@example.test', 'car' => 'toyota-innova-zenix-2-0-q-hv-2025', 'day' => -10, 'hour' => 15,
                'status' => TestDrive::STATUS_COMPLETED],
            ['email' => 'maya.putri@example.test', 'car' => 'honda-brio-satya-e-cvt-2025', 'day' => -2, 'hour' => 9,
                'status' => TestDrive::STATUS_CANCELLED, 'admin_note' => 'Customer meminta pembatalan lewat telepon.'],
            ['email' => 'budi.santoso@example.test', 'car' => 'honda-civic-1-5-rs-turbo-2022', 'day' => 6, 'hour' => 10,
                'status' => TestDrive::STATUS_CANCELLED, 'admin_note' => 'Unit sudah terjual, customer ditawari mobil lain.'],
        ];
    }
}
