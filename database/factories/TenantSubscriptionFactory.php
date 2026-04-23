<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<TenantSubscription>
 */
class TenantSubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = TenantSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = now()->subDays(15);
        $endDate = $startDate->copy()->addMonth();

        return [
            'tenant_id' => Tenant::factory(),
            'subscription_plan_id' => 1, // Assuming basic plan exists
            'status' => 'active',
            'billing_cycle' => $this->faker->randomElement(['monthly', 'yearly']),
            'price' => $this->faker->randomFloat(2, 29.99, 299.99),
            'currency' => 'ARS',
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'trial_ends_at' => null,
            'cancelled_at' => null,
            'next_billing_at' => $endDate->copy()->addDay(),
            'payment_method' => 'manual',
            'stripe_subscription_id' => null,
            'stripe_customer_id' => null,
            'auto_renew' => true,
            'cancellation_reason' => null,
            'usage_stats' => [
                'products_created' => $this->faker->numberBetween(0, 1000),
                'sales_made' => $this->faker->numberBetween(0, 500),
                'storage_used_mb' => $this->faker->randomFloat(2, 0, 1000),
            ],
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'trial_ends_at' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * Indicate that the subscription is in trial period.
     */
    public function trial(?Carbon $trialEndsAt = null): static
    {
        $trialEndsAt = $trialEndsAt ?? now()->addDays(14);

        return $this->state(fn (array $attributes) => [
            'status' => 'trial',
            'trial_ends_at' => $trialEndsAt,
            'starts_at' => now()->subDays(7),
            'ends_at' => $trialEndsAt,
        ]);
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now()->subDays($this->faker->numberBetween(1, 30)),
            'cancellation_reason' => $reason ?? $this->faker->sentence(),
            'auto_renew' => false,
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'ends_at' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the subscription is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the subscription is for a specific tenant.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Indicate that the subscription has monthly billing cycle.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'monthly',
        ]);
    }

    /**
     * Indicate that the subscription has yearly billing cycle.
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'yearly',
            'price' => $attributes['price'] * 10, // Yearly discount
        ]);
    }

    /**
     * Indicate that the subscription auto-renews.
     */
    public function autoRenew(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_renew' => true,
        ]);
    }

    /**
     * Indicate that the subscription does not auto-renew.
     */
    public function noAutoRenew(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_renew' => false,
        ]);
    }

    /**
     * Indicate that the subscription is managed by Stripe.
     */
    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'stripe',
            'stripe_subscription_id' => 'sub_'.$this->faker->unique()->sha1(),
            'stripe_customer_id' => 'cus_'.$this->faker->unique()->sha1(),
        ]);
    }
}
