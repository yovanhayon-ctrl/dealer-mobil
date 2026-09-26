<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * 8 customer dummy (email @example.test) untuk seeder test drive & pengajuan.
     * Hanya berjalan di environment local/testing, kata sandi dari SEED_CUSTOMER_PASSWORD (.env).
     * Aman dijalankan ulang: dicari berdasarkan email; data, role, dan kata sandi user yang sudah ada tidak diubah.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('CustomerSeeder dilewati: data customer dummy hanya untuk environment local/testing.');

            return;
        }

        $password = config('dealer.seed.customer_password');

        if (blank($password)) {
            $this->command?->warn('CustomerSeeder dilewati: SEED_CUSTOMER_PASSWORD belum diisi di .env.');

            return;
        }

        $created = 0;

        foreach ($this->customers() as [$name, $email, $phone]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'phone' => $phone,
                'password' => $password,
            ]);

            if ($user->wasRecentlyCreated) {
                // Role memakai default kolom (customer); email dummy dianggap sudah terverifikasi.
                $user->email_verified_at = now();
                $user->save();
                $created++;
            }
        }

        $this->command?->info(count($this->customers())." customer dummy siap ({$created} baru).");
    }

    /**
     * @return array<int, array{string, string, string}>
     */
    private function customers(): array
    {
        return [
            ['Budi Santoso', 'budi.santoso@example.test', '081234567801'],
            ['Siti Rahmawati', 'siti.rahmawati@example.test', '081234567802'],
            ['Andi Pratama', 'andi.pratama@example.test', '081234567803'],
            ['Dewi Lestari', 'dewi.lestari@example.test', '081234567804'],
            ['Rizky Hidayat', 'rizky.hidayat@example.test', '081234567805'],
            ['Nur Aisyah', 'nur.aisyah@example.test', '081234567806'],
            ['Agus Setiawan', 'agus.setiawan@example.test', '081234567807'],
            ['Maya Putri', 'maya.putri@example.test', '081234567808'],
        ];
    }
}
