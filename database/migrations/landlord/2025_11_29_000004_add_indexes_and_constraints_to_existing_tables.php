<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->table('tenant_subscriptions', function (Blueprint $table) {
            // Add missing indexes
            $table->index(['status', 'payment_status']);
            $table->index('billing_cycle');
            $table->index('payment_method');
            $table->index('created_at');

            // Add unique constraint for active subscriptions per tenant
            $table->unique(['tenant_id'], 'unique_active_tenant_subscription');
        });

        Schema::connection('landlord')->table('payment_transactions', function (Blueprint $table) {
            // Add missing indexes
            $table->index('amount');
            $table->index('currency');
            $table->index('transaction_id');
            $table->index('approved_by');
            $table->index('approved_at');
        });

        Schema::connection('landlord')->table('subscription_plans', function (Blueprint $table) {
            // Add missing indexes
            $table->index('currency');
            $table->index(['is_active', 'sort_order']);
            $table->index('price_monthly');
            $table->index('price_yearly');
        });

        Schema::connection('landlord')->table('payment_gateway_settings', function (Blueprint $table) {
            // Add missing indexes
            $table->index('driver_name');
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->table('tenant_subscriptions', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['status', 'payment_status']);
            $table->dropIndex('billing_cycle');
            $table->dropIndex('payment_method');
            $table->dropIndex('created_at');

            // Drop unique constraint
            $table->dropUnique('unique_active_tenant_subscription');
        });

        Schema::connection('landlord')->table('payment_transactions', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('amount');
            $table->dropIndex('currency');
            $table->dropIndex('transaction_id');
            $table->dropIndex('approved_by');
            $table->dropIndex('approved_at');
        });

        Schema::connection('landlord')->table('subscription_plans', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('currency');
            $table->dropIndex(['is_active', 'sort_order']);
            $table->dropIndex('price_monthly');
            $table->dropIndex('price_yearly');
        });

        Schema::connection('landlord')->table('payment_gateway_settings', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('driver_name');
            $table->dropIndex(['is_active', 'sort_order']);
        });
    }
};