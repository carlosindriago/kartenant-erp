<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('subscription_plans', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('name'); // Starter, Professional, Enterprise
            $table->string('slug')->unique(); // starter, professional, enterprise
            $table->text('description')->nullable();

            // Pricing
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->string('currency')->default('USD');

            // Trial
            $table->boolean('has_trial')->default(false);
            $table->integer('trial_days')->default(0);

            // Limits
            $table->integer('max_users')->nullable(); // null = unlimited
            $table->integer('max_products')->nullable();
            $table->integer('max_sales_per_month')->nullable();
            $table->integer('max_storage_mb')->nullable();

            // Features (JSON for flexibility)
            $table->json('enabled_modules')->nullable(); // ['inventory', 'pos', 'clients']
            $table->json('features')->nullable(); // Custom features per plan

            // Settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true); // Show in public pricing page
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);

            // Stripe/Payment integration
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_monthly_id')->nullable();
            $table->string('stripe_price_yearly_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('subscription_plans');
    }
};
