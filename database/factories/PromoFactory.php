<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\Promo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Promo>
 */
class PromoFactory extends Factory
{
    /**
     * Default: promo umum (tanpa mobil & diskon) yang sedang berjalan.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Promo '.Str::title(fake()->words(3, true));

        return [
            'car_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'description' => fake()->sentence(),
            'image' => null,
            'discount_amount' => null,
            'start_date' => today()->subDays(7),
            'end_date' => today()->addDays(23),
            'is_active' => true,
        ];
    }

    /**
     * Promo khusus mobil; diskon default ±5% dari harga mobil.
     */
    public function forCar(Car $car, ?int $discount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'car_id' => $car->id,
            'discount_amount' => $discount ?? intdiv($car->price, 20),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => today()->addDays(10),
            'end_date' => today()->addDays(40),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => today()->subDays(60),
            'end_date' => today()->subDays(30),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
