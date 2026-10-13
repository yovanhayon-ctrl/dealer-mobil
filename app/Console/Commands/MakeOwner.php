<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Jadikan akun yang sudah ada sebagai owner (pemilik dealer). Owner bisa mengelola akun staf di admin.
 * Contoh: php artisan user:make-owner pemilik@dealer.com
 */
#[Signature('user:make-owner {email : Email akun yang sudah terdaftar}')]
#[Description('Jadikan akun sebagai owner (pemilik dealer)')]
class MakeOwner extends Command
{
    public function handle(): int
    {
        $user = User::firstWhere('email', $this->argument('email'));

        if (! $user) {
            $this->error('Akun dengan email itu tidak ditemukan. Buat akunnya dulu (mis. lewat ADMIN_EMAIL + db:seed).');

            return self::FAILURE;
        }

        $user->role = User::ROLE_OWNER;
        $user->is_active = true;
        $user->save();

        $this->info("{$user->name} ({$user->email}) sekarang owner.");

        return self::SUCCESS;
    }
}
