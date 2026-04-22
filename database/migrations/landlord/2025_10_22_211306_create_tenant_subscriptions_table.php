<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->onDelete('restrict');

            // Subscription details
            $table->string('status')->default('active'); // active, cancelled, expired, suspended
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            $table->decimal('price', 10, 2);
            $table->string('currency')->default('USD');

            // Dates
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('next_billing_at')->nullable();

            // Payment
            $table->string('payment_method')->nullable(); // stripe, paypal, mercadopago, manual
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_customer_id')->nullable();

            // Auto-renewal
            $table->boolean('auto_renew')->default(true);
            $table->text('cancellation_reason')->nullable();

            // Usage tracking (for metered billing if needed)
            $table->json('usage_stats')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('status');
            $table->index('next_billing_at');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('tenant_subscriptions');
    }
};
