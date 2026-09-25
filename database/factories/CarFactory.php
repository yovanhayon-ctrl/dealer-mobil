<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->words(2, true)).' '.fake()->bothify('##??');

        return [
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'vehicle_condition' => Car::CONDITION_NEW,
            'year' => (int) now()->year,
            'mileage' => 0,
            'price' => fake()->numberBetween(150, 900) * 1_000_000,
            'transmission' => fake()->randomElement(['manual', 'automatic']),
            'fuel_type' => fake()->randomElement(['bensin', 'diesel', 'hybrid', 'listrik']),
            'engine_cc' => fake()->randomElement([1000, 1200, 1500, 2000, 2400]),
            'seats' => fake()->randomElement([5, 7, 8]),
            'color' => fake()->randomElement(['Putih', 'Hitam', 'Silver', 'Merah', 'Abu-abu']),
            'stock' => fake()->numberBetween(1, 10),
            'description' => fake()->paragraph(),
            'is_active' => true,
        ];
    }

    /**
     * Mobil bekas dengan tahun dan kilometer acak, stok satu unit.
     */
    public function used(): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_condition' => Car::CONDITION_USED,
            'year' => fake()->numberBetween(now()->year - 10, now()->year - 1),
            'mileage' => fake()->numberBetween(5, 150) * 1_000,
            'stock' => 1,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
