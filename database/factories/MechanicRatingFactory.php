<?php

namespace Database\Factories;

use App\Models\MechanicRating;
use App\Models\ServiceBooking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MechanicRating>
 */
class MechanicRatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Urutan penting: booking dibuat setelah mekaniknya ada.
            'mechanic_id' => User::factory()->mechanic(),
            'service_booking_id' => fn (array $attributes) => ServiceBooking::factory()->create([
                'status' => ServiceBooking::STATUS_COMPLETED,
                'mechanic_id' => $attributes['mechanic_id'],
            ])->id,
            // Pemberi rating = pemilik booking.
            'user_id' => fn (array $attributes) => ServiceBooking::find($attributes['service_booking_id'])->user_id,
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
