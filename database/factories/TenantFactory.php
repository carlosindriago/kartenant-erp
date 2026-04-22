<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();
        $domain = $this->faker->unique()->slug(2) . '.test.com';

        return [
            'name' => $name,
            'domain' => $domain,
            'database' => 'tenant_' . str_replace('.', '_', str_replace('-', '_', $domain)),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'cuit' => $this->faker->numerify('#############'),
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->companyEmail(),
            'plan' => $this->faker->randomElement(['basic', 'premium', 'enterprise']),
            'status' => Tenant::STATUS_ACTIVE,
            'trial_ends_at' => null,
            'timezone' => $this->faker->timezone(),
            'locale' => 'es',
            'currency' => 'ARS',
            'verification_access_type' => 'all',
            'verification_allowed_roles' => ['admin', 'manager'],
            'verification_enabled' => true,
            // Branding fields
            'logo_type' => 'text',
            'company_display_name' => $name,
            'logo_path' => null,
            'logo_background_color' => '#3b82f6',
            'logo_text_color' => '#ffffff',
        ];
    }

    /**
     * Indicate that the tenant is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_ACTIVE,
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the tenant is in trial period.
     */
    public function trial(?string $trialEndsAt = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_TRIAL,
            'trial_ends_at' => $trialEndsAt ?? now()->addDays(30),
        ]);
    }

    /**
     * Indicate that the tenant is in trial period but expired.
     */
    public function trialExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_TRIAL,
            'trial_ends_at' => now()->subDays(1),
        ]);
    }

    /**
     * Indicate that the tenant is in trial period without end date.
     */
    public function trialWithoutEnd(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_TRIAL,
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the tenant is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_SUSPENDED,
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the tenant is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_EXPIRED,
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the tenant is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_ARCHIVED,
            'trial_ends_at' => null,
        ]);
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tenant::STATUS_INACTIVE,
            'trial_ends_at' => null,
        ]);
    }
}