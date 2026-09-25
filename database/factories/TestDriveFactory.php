<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\TestDrive;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestDrive>
 */
class TestDriveFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'car_id' => Car::factory(),
            'preferred_date' => today()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'preferred_time' => sprintf('%02d:00', fake()->numberBetween(9, 16)),
            'phone' => '081'.fake()->numerify('#########'),
            'notes' => fake()->optional()->sentence(),
            'status' => 'pending',
            'admin_note' => null,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
