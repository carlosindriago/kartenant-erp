<?php

namespace Database\Factories;

use App\Models\PaymentTransaction;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = PaymentTransaction::class;

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
            'gateway_driver' => $this->faker->randomElement(['stripe', 'paypal', 'manual', 'mercado_pago']),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'currency' => 'ARS',
            'status' => PaymentTransaction::STATUS_PENDING,
            'transaction_id' => $this->faker->unique()->sha1(),
            'proof_of_payment' => $this->faker->optional(0.3)->url(),
            'metadata' => [
                'gateway_response' => $this->faker->sentences(3, true),
                'processing_time_ms' => $this->faker->numberBetween(1000, 10000),
                'retry_count' => $this->faker->numberBetween(0, 3),
            ],
            'approved_by' => null,
            'approved_at' => null,
        ];
    }

    /**
     * Indicate that the payment transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentTransaction::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the payment transaction is approved.
     */
    public function approved(?User $approver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentTransaction::STATUS_APPROVED,
            'approved_by' => $approver?->id ?? User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment transaction is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentTransaction::STATUS_REJECTED,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the payment transaction is completed.
     */
    public function completed(?User $approver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentTransaction::STATUS_COMPLETED,
            'approved_by' => $approver?->id ?? User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment transaction has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentTransaction::STATUS_FAILED,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the payment transaction uses Stripe gateway.
     */
    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway_driver' => 'stripe',
            'transaction_id' => 'ch_' . $this->faker->unique()->sha1(),
        ]);
    }

    /**
     * Indicate that the payment transaction uses PayPal gateway.
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway_driver' => 'paypal',
            'transaction_id' => 'PAYID-' . $this->faker->unique()->sha1(),
        ]);
    }

    /**
     * Indicate that the payment transaction uses Mercado Pago gateway.
     */
    public function mercadoPago(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway_driver' => 'mercado_pago',
            'transaction_id' => 'mp_' . $this->faker->unique()->numerify('##########'),
        ]);
    }

    /**
     * Indicate that the payment transaction is manual.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway_driver' => 'manual',
            'transaction_id' => 'MANUAL-' . $this->faker->unique()->numerify('##########'),
        ]);
    }

    /**
     * Indicate that the payment transaction has proof of payment.
     */
    public function withProofOfPayment(?string $proofUrl = null): static
    {
        return $this->state(fn (array $attributes) => [
            'proof_of_payment' => $proofUrl ?? $this->faker->url(),
        ]);
    }

    /**
     * Indicate that the payment transaction is for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Indicate that the payment transaction is for a specific subscription.
     */
    public function forSubscription(TenantSubscription $subscription): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
        ]);
    }

    /**
     * Indicate that the payment transaction is a recurring payment.
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'is_recurring' => true,
                'recurring_period' => 'monthly',
                'next_billing_date' => now()->addMonth()->toDateString(),
            ]),
        ]);
    }

    /**
     * Indicate that the payment transaction has a retry attempt.
     */
    public function withRetries(int $retryCount = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'retry_count' => $retryCount,
                'last_retry_at' => now()->subMinutes($retryCount * 5)->toDateTimeString(),
            ]),
        ]);
    }
}