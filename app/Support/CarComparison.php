<?php

namespace App\Support;

use App\Models\Car;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Collection;

/**
 * Daftar mobil yang dibandingkan, disimpan di session (tamu juga bisa, tanpa tabel database).
 */
class CarComparison
{
    public const MAX_CARS = 3;

    private const SESSION_KEY = 'compare.cars';

    public function __construct(private Session $session) {}

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_filter((array) $this->session->get(self::SESSION_KEY, []), 'is_int'));
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function has(Car $car): bool
    {
        return in_array($car->id, $this->ids(), true);
    }

    /**
     * @return 'added'|'exists'|'full'
     */
    public function add(Car $car): string
    {
        $ids = $this->ids();

        if (in_array($car->id, $ids, true)) {
            return 'exists';
        }

        if (count($ids) >= self::MAX_CARS) {
            return 'full';
        }

        $this->session->put(self::SESSION_KEY, [...$ids, $car->id]);

        return 'added';
    }

    public function remove(Car $car): void
    {
        $this->session->put(self::SESSION_KEY, array_values(array_diff($this->ids(), [$car->id])));
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * Mobil aktif sesuai urutan dipilih. Mobil yang sudah dinonaktifkan / dihapus admin dibuang dari session.
     *
     * @return Collection<int, Car>
     */
    public function cars(): Collection
    {
        $ids = $this->ids();

        $cars = Car::active()
            ->with(Car::CARD_RELATIONS)
            ->whereKey($ids)
            ->get()
            ->sortBy(fn (Car $car) => array_search($car->id, $ids, true))
            ->values();

        if ($cars->count() !== count($ids)) {
            $this->session->put(self::SESSION_KEY, $cars->modelKeys());
        }

        return $cars;
    }
}
