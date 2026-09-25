<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Buat akun admin dari ADMIN_EMAIL / ADMIN_PASSWORD (.env).
     * Aman dijalankan ulang: jika email sudah ada, hanya memastikan role admin
     * (kata sandi tidak ditimpa).
     */
    public function run(): void
    {
        $email = config('dealer.admin.email');
        $password = config('dealer.admin.password');

        if (blank($email) || blank($password)) {
            throw new RuntimeException('ADMIN_EMAIL / ADMIN_PASSWORD belum diisi di .env.');
        }

        $user = User::firstWhere('email', $email);

        if ($user) {
            $user->role = User::ROLE_ADMIN;
            $user->save();

            $this->command?->info("Akun admin {$email} sudah ada, role dipastikan admin.");

            return;
        }

        $user = new User([
            'name' => 'Administrator',
            'email' => $email,
            'password' => $password,
        ]);
        $user->role = User::ROLE_ADMIN;
        $user->email_verified_at = now();
        $user->save();

        $this->command?->info("Akun admin {$email} berhasil dibuat.");
    }
}
