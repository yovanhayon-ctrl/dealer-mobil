<?php

namespace Database\Factories;

use App\Models\PaymentRefund;
use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRefund>
 */
class PaymentRefundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_request_id' => PurchaseRequest::factory()->status(PurchaseRequest::STATUS_CANCELLED),
            'amount' => 5_000_000,
            'refunded_on' => now()->toDateString(),
            'method' => PaymentRefund::METHOD_TRANSFER,
            'file_path' => null,
            'file_name' => null,
            'note' => null,
            'recorded_by' => null,
        ];
    }
}
