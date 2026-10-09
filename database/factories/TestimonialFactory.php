<?php

namespace Database\Factories;

use App\Models\PurchaseRequest;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_request_id' => PurchaseRequest::factory()->status(PurchaseRequest::STATUS_COMPLETED),
            // Pemilik ulasan = pemilik pengajuan.
            'user_id' => fn (array $attributes) => PurchaseRequest::find($attributes['purchase_request_id'])->user_id,
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->paragraph(),
            'status' => Testimonial::STATUS_PENDING,
            'admin_note' => null,
            'approved_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Testimonial::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Ulasan berisi data pribadi.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Testimonial::STATUS_REJECTED,
            'admin_note' => $reason,
        ]);
    }
}
