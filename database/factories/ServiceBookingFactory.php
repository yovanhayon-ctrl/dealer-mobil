<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceBooking>
 */
class ServiceBookingFactory extends Factory
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
            'service_id' => Service::factory(),
            'vehicle_model' => fake()->randomElement(['Nissan Livina VL', 'Nissan X-Trail', 'Nissan Juke', 'Nissan Grand Livina']),
            'plate_number' => strtoupper(fake()->bothify('B #### ???')),
            'vehicle_year' => fake()->numberBetween(2012, 2025),
            'mileage' => fake()->numberBetween(1, 150) * 1_000,
            'preferred_date' => today()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'preferred_time' => sprintf('%02d:00', fake()->numberBetween(8, 15)),
            'phone' => '081'.fake()->numerify('#########'),
            'complaint' => fake()->optional()->sentence(),
            'status' => ServiceBooking::STATUS_PENDING,
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
