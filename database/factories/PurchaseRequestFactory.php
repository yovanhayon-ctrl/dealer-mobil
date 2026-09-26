<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\CreditCalculator;
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
     * Pembayaran kredit: DP minimal (20%), tenor 36 bulan; bunga & cicilan dari CreditCalculator (config/credit.php).
     */
    public function credit(): static
    {
        return $this->state(function (array $attributes) {
            $calculator = app(CreditCalculator::class);
            $price = $attributes['car_price'];
            $credit = $calculator->calculate($price, $calculator->minDownPayment($price), 36);

            return [
                'payment_method' => 'credit',
                'down_payment' => $credit['down_payment'],
                'tenor_months' => $credit['tenor_months'],
                'interest_rate' => $credit['interest_rate'],
                'monthly_installment' => $credit['monthly_installment'],
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
