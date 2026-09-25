<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    /**
     * Define the model's default state (pembayaran cash).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'car_id' => Car::factory(),
            'car_price' => fake()->numberBetween(150, 900) * 1_000_000,
            'payment_method' => 'cash',
            'down_payment' => null,
            'tenor_months' => null,
            'interest_rate' => null,
            'monthly_installment' => null,
            'phone' => '081'.fake()->numerify('#########'),
            'address' => fake()->address(),
            'notes' => fake()->optional()->sentence(),
            'status' => 'pending',
            'admin_note' => null,
        ];
    }

    /**
     * Pembayaran kredit: DP 20%, tenor 36 bulan, bunga flat 6%/tahun (rumus RANCANGAN §5).
     */
    public function credit(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['car_price'];
            $downPayment = (int) ($price * 0.2);
            $principal = $price - $downPayment;
            $interest = $principal * 0.06 * (36 / 12);

            return [
                'payment_method' => 'credit',
                'down_payment' => $downPayment,
                'tenor_months' => 36,
                'interest_rate' => 6,
                'monthly_installment' => (int) (ceil(($principal + $interest) / 36 / 1_000) * 1_000),
            ];
        });
    }

    public function status(string $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
