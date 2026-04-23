<?php

namespace Database\Factories;

use App\Models\PaymentProof;
use App\Models\PaymentTransaction;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentProof>
 */
class PaymentProofFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = PaymentProof::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => TenantSubscription::factory(),
            'payment_transaction_id' => null,
            'payment_method' => $this->faker->randomElement([
                PaymentProof::PAYMENT_METHOD_BANK_TRANSFER,
                PaymentProof::PAYMENT_METHOD_CASH,
                PaymentProof::PAYMENT_METHOD_MOBILE_MONEY,
                PaymentProof::PAYMENT_METHOD_OTHER,
            ]),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'ARS',
            'payment_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'reference_number' => $this->faker->unique()->numerify('TRANS-########'),
            'payer_name' => $this->faker->name(),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'file_paths' => [
                'payment_proofs/'.$this->faker->uuid().'.jpg',
                'payment_proofs/'.$this->faker->uuid().'.pdf',
            ],
            'file_type' => $this->faker->randomElement(['image', 'pdf', 'mixed']),
            'total_file_size_mb' => $this->faker->randomFloat(2, 0.5, 10),
            'status' => PaymentProof::STATUS_PENDING,
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_notes' => null,
            'metadata' => [
                'uploaded_from_ip' => $this->faker->ipv4(),
                'browser' => $this->faker->userAgent(),
                'upload_duration_ms' => $this->faker->numberBetween(1000, 10000),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }

    /**
     * Indicate that the payment proof is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentProof::STATUS_PENDING,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the payment proof is under review.
     */
    public function underReview(?User $reviewer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentProof::STATUS_UNDER_REVIEW,
            'reviewed_by' => $reviewer?->id ?? User::factory(),
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the payment proof is approved.
     */
    public function approved(?User $reviewer = null, ?string $notes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentProof::STATUS_APPROVED,
            'reviewed_by' => $reviewer?->id ?? User::factory(),
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Indicate that the payment proof is rejected.
     */
    public function rejected(?User $reviewer = null, ?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentProof::STATUS_REJECTED,
            'reviewed_by' => $reviewer?->id ?? User::factory(),
            'reviewed_at' => now(),
            'rejection_reason' => $reason ?? $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the payment proof is for bank transfer.
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentProof::PAYMENT_METHOD_BANK_TRANSFER,
        ]);
    }

    /**
     * Indicate that the payment proof is for cash payment.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentProof::PAYMENT_METHOD_CASH,
        ]);
    }

    /**
     * Indicate that the payment proof is for mobile money.
     */
    public function mobileMoney(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentProof::PAYMENT_METHOD_MOBILE_MONEY,
        ]);
    }

    /**
     * Indicate that the payment proof is for other methods.
     */
    public function other(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => PaymentProof::PAYMENT_METHOD_OTHER,
        ]);
    }

    /**
     * Indicate that the payment proof has no files attached.
     */
    public function withoutFiles(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_paths' => [],
            'file_type' => null,
            'total_file_size_mb' => 0,
        ]);
    }

    /**
     * Indicate that the payment proof has a single image file.
     */
    public function singleImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_paths' => [
                'payment_proofs/'.$this->faker->uuid().'.jpg',
            ],
            'file_type' => 'image',
            'total_file_size_mb' => $this->faker->randomFloat(2, 0.5, 5),
        ]);
    }

    /**
     * Indicate that the payment proof has PDF files.
     */
    public function pdfFiles(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_paths' => [
                'payment_proofs/'.$this->faker->uuid().'.pdf',
            ],
            'file_type' => 'pdf',
            'total_file_size_mb' => $this->faker->randomFloat(2, 0.5, 3),
        ]);
    }

    /**
     * Indicate that the payment proof has associated payment transaction.
     */
    public function withPaymentTransaction(?PaymentTransaction $transaction = null): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_transaction_id' => $transaction?->id ?? PaymentTransaction::factory(),
        ]);
    }

    /**
     * Indicate that the payment proof is for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Indicate that the payment proof is for a specific subscription.
     */
    public function forSubscription(TenantSubscription $subscription): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);
    }
}
