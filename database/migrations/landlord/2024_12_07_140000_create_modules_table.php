<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('landlord')->create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('base_price_monthly', 10, 2)->default(0.00);
            $table->decimal('base_price_yearly', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            $table->decimal('setup_fee', 10, 2)->nullable();
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('category')->nullable();
            $table->string('icon')->nullable();
            $table->string('version')->nullable();
            $table->string('provider')->nullable();
            $table->json('limits')->nullable();
            $table->json('configuration')->nullable();
            $table->json('dependencies')->nullable();
            $table->json('conflicts')->nullable();
            $table->json('permissions')->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->boolean('auto_renew')->default(true);
            $table->integer('trial_days')->default(0);
            $table->json('billing_tiers')->nullable();
            $table->json('feature_flags')->nullable();
            $table->json('routes')->nullable();
            $table->json('resources')->nullable();
            $table->json('menu_items')->nullable();
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_monthly_id')->nullable();
            $table->string('stripe_price_yearly_id')->nullable();
            $table->json('api_endpoints')->nullable();
            $table->integer('installations_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['is_active', 'is_visible', 'sort_order']);
            $table->index(['category', 'is_active']);
            $table->index('is_custom');
            $table->index('is_featured');
            $table->index('slug');
            $table->index('billing_cycle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('modules');
    }
};
