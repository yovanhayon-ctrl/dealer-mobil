<?php

namespace Database\Seeders\Concerns;

use App\Models\Car;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Bantuan seeder data dummy transaksi (test drive & pengajuan): hanya local/testing,
 * memakai customer dummy @example.test (CustomerSeeder) dan mobil dari CarSeeder.
 */
trait SeedsDummyTransactions
{
    private function allowedEnvironment(): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return true;
        }

        $this->command?->warn(static::class.' dilewati: data dummy hanya untuk environment local/testing.');

        return false;
    }

    /**
     * Customer dummy berdasarkan email (hanya role customer).
     *
     * @return Collection<string, User>
     */
    private function dummyCustomers(): Collection
    {
        return User::where('role', User::ROLE_CUSTOMER)
            ->where('email', 'like', '%@example.test')
            ->get(['id', 'email', 'phone'])
            ->keyBy('email');
    }

    /**
     * @param  array<int, string>  $slugs
     * @return Collection<string, Car>
     */
    private function carsBySlug(array $slugs): Collection
    {
        return Car::whereIn('slug', $slugs)->get()->keyBy('slug');
    }

    /**
     * true bila customer & mobil tersedia; selain itu tampilkan peringatan.
     */
    private function hasPrerequisites(Collection $customers, Collection $cars): bool
    {
        if ($customers->isEmpty()) {
            $this->command?->warn(static::class.' dilewati: customer dummy @example.test belum ada (isi SEED_CUSTOMER_PASSWORD lalu jalankan CustomerSeeder).');

            return false;
        }

        if ($cars->isEmpty()) {
            $this->command?->warn(static::class.' dilewati: mobil belum ada (jalankan CarSeeder).');

            return false;
        }

        return true;
    }
}
