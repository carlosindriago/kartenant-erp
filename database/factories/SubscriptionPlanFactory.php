<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->slug,
            'description' => $this->faker->sentence,
            'price_monthly' => $this->faker->randomFloat(2, 10, 100),
            'price_yearly' => $this->faker->randomFloat(2, 100, 1000),
            'currency' => 'USD',
            'has_trial' => true,
            'trial_days' => 14,
            'is_active' => true,
            'is_visible' => true,
            'sort_order' => $this->faker->numberBetween(1, 10),
            'features' => ['feature1', 'feature2'],
            'limits' => ['users' => 5],
            'overage_strategy' => 'soft',
            'overage_percentage' => 20,
            'overage_tolerance' => 0,
        ];
    }
}
