<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('tenant_usages', function (Blueprint $table) {
            $table->id();

            // Tenant relation
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Tracking period (monthly cycles)
            $table->year('year');
            $table->tinyInteger('month'); // 1-12

            // Current usage counters
            $table->unsignedInteger('sales_count')->default(0);
            $table->unsignedInteger('products_count')->default(0);
            $table->unsignedInteger('users_count')->default(0);
            $table->unsignedInteger('storage_size_mb')->default(0);

            // Limits (copied from subscription_plan for performance)
            $table->unsignedInteger('max_sales_per_month')->nullable();
            $table->unsignedInteger('max_products')->nullable();
            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('max_storage_mb')->nullable();

            // Usage percentages (calculated)
            $table->decimal('sales_percentage', 5, 2)->default(0); // 0.00 - 999.99
            $table->decimal('products_percentage', 5, 2)->default(0);
            $table->decimal('users_percentage', 5, 2)->default(0);
            $table->decimal('storage_percentage', 5, 2)->default(0);

            // Overall status (based on highest percentage)
            $table->enum('status', ['normal', 'warning', 'overdraft', 'critical'])->default('normal');

            // Flags for billing and alerts
            $table->boolean('upgrade_required_next_cycle')->default(false);
            $table->boolean('warning_sent')->default(false);
            $table->boolean('overdraft_sent')->default(false);
            $table->boolean('critical_sent')->default(false);

            // Last activity tracking
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamp('last_alert_sent_at')->nullable();

            $table->timestamps();

            // Unique constraint for tenant + period
            $table->unique(['tenant_id', 'year', 'month'], 'tenant_usage_period_unique');

            // Indexes for performance
            $table->index(['tenant_id', 'year', 'month']);
            $table->index('status');
            $table->index('upgrade_required_next_cycle');
            $table->index(['year', 'month']); // For batch processing
        });

        // Usage alerts history
        Schema::connection('landlord')->create('usage_alerts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('tenant_usage_id')->nullable()->constrained('tenant_usages')->onDelete('set null');

            // Alert details
            $table->enum('alert_type', ['warning', 'overdraft', 'critical']);
            $table->enum('metric_type', ['sales', 'products', 'users', 'storage', 'overall']);

            // Usage snapshot at time of alert
            $table->unsignedInteger('current_value')->default(0);
            $table->unsignedInteger('limit_value')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);

            // Delivery tracking
            $table->json('delivery_channels'); // ['email', 'slack', 'in_app']
            $table->json('delivery_status'); // {"email": "sent", "slack": "failed"}
            $table->text('message')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Additional context

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'alert_type']);
            $table->index(['tenant_usage_id', 'alert_type']);
            $table->index('created_at');
        });

        // Usage metrics log (for analytics and debugging)
        Schema::connection('landlord')->create('usage_metrics_log', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Metric details
            $table->enum('metric_type', ['sale_created', 'product_created', 'user_created', 'storage_used']);
            $table->string('entity_type')->nullable(); // 'Product', 'User', 'Sale', etc.
            $table->unsignedBigInteger('entity_id')->nullable();

            // Metric value (usually 1 for counts, or size in bytes for storage)
            $table->unsignedInteger('value')->default(0);

            // Context
            $table->string('source')->default('system'); // 'observer', 'manual', 'migration'
            $table->json('metadata')->nullable(); // Additional context

            $table->timestamp('created_at')->nullable();

            // Indexes for performance and analytics
            $table->index(['tenant_id', 'metric_type']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('usage_metrics_log');
        Schema::connection('landlord')->dropIfExists('usage_alerts');
        Schema::connection('landlord')->dropIfExists('tenant_usages');
    }
};
