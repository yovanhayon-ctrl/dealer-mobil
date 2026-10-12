<?php

namespace Database\Factories;

use App\Models\PaymentProof;
use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentProof>
 */
class PaymentProofFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_request_id' => PurchaseRequest::factory()->status(PurchaseRequest::STATUS_APPROVED),
            // Pengunggah = pemilik pengajuan.
            'user_id' => fn (array $attributes) => PurchaseRequest::find($attributes['purchase_request_id'])->user_id,
            'type' => PaymentProof::TYPE_BOOKING_FEE,
            'amount' => 5_000_000,
            'paid_on' => now()->toDateString(),
            'file_path' => fn (array $attributes) => PaymentProof::directory($attributes['purchase_request_id']).'/'.fake()->uuid().'.jpg',
            'file_name' => 'bukti-transfer.jpg',
            'note' => null,
            'status' => PaymentProof::STATUS_PENDING,
            'admin_note' => null,
            'verified_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => PaymentProof::STATUS_APPROVED, 'verified_at' => now()]);
    }

    public function rejected(string $reason = 'Nominal tidak sesuai mutasi rekening.'): static
    {
        return $this->state(fn () => ['status' => PaymentProof::STATUS_REJECTED, 'admin_note' => $reason, 'verified_at' => now()]);
    }
}
