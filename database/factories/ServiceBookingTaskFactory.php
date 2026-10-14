<?php

namespace Database\Factories;

use App\Models\ServiceBooking;
use App\Models\ServiceBookingTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceBookingTask>
 */
class ServiceBookingTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_booking_id' => ServiceBooking::factory(),
            'name' => fake()->randomElement(['Ganti oli mesin', 'Ganti filter oli', 'Cek dan setel rem', 'Cek aki', 'Cek tekanan ban']),
            'position' => fake()->numberBetween(1, 10),
            'status' => ServiceBookingTask::STATUS_PENDING,
            'note' => null,
            'checked_at' => null,
        ];
    }
}
