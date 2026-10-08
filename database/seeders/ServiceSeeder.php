<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Layanan JAF Service sesuai proposal (bagian 04). Aman dijalankan ulang:
     * layanan yang sudah ada (berdasarkan slug) tidak diubah, sehingga perubahan dari admin tetap.
     */
    public function run(): void
    {
        foreach ($this->services() as $service) {
            Service::firstOrCreate(['slug' => Service::slugify($service['name'])], $service + ['is_active' => true]);
        }
    }

    /**
     * Harga "mulai dari" & durasi adalah estimasi contoh (data fiktif).
     *
     * @return array<int, array<string, mixed>>
     */
    private function services(): array
    {
        return [
            ['name' => 'Servis Berkala', 'price_from' => 450_000, 'duration_minutes' => 120,
                'description' => 'Pemeriksaan rutin, penggantian oli dan filter, serta pengecekan komponen sesuai jadwal perawatan kendaraan.'],
            ['name' => 'Inspeksi Kendaraan', 'price_from' => 250_000, 'duration_minutes' => 60,
                'description' => 'Pengecekan mesin, rem, ban, aki, AC, dan komponen penting untuk mengetahui kondisi kendaraan lebih awal.'],
            ['name' => 'Perawatan Ringan', 'price_from' => 300_000, 'duration_minutes' => 90,
                'description' => 'Perawatan atau penggantian komponen yang mengalami penurunan fungsi agar kendaraan tetap nyaman digunakan.'],
            ['name' => 'Ban & Aki', 'price_from' => 150_000, 'duration_minutes' => 60,
                'description' => 'Pemeriksaan dan penggantian ban serta aki sesuai kondisi kendaraan untuk keamanan dan kenyamanan berkendara.'],
            ['name' => 'Detailing', 'price_from' => 600_000, 'duration_minutes' => 180,
                'description' => 'Perawatan kebersihan eksterior dan interior untuk menjaga tampilan kendaraan.'],
            ['name' => 'Suku Cadang', 'price_from' => null, 'duration_minutes' => null,
                'description' => 'Penyediaan dan pemasangan suku cadang yang diperlukan untuk perawatan. Harga mengikuti komponen yang dibutuhkan.'],
        ];
    }
}
