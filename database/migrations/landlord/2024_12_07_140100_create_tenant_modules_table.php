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
        Schema::connection('landlord')->create('tenant_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            $table->decimal('price_override', 10, 2)->nullable();
            $table->string('currency_override', 3)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_renew')->default(true);
            $table->string('billing_cycle')->default('monthly');
            $table->string('status')->default('active');
            $table->json('configuration')->nullable();
            $table->json('limits_override')->nullable();
            $table->json('metadata')->nullable();
            $table->json('usage_stats')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('next_billing_at')->nullable();
            $table->foreignId('tenant_subscription_id')->nullable()->constrained('tenant_subscriptions')->onDelete('set null');
            $table->foreignId('invoice_line_item_id')->nullable(); // TODO: Add constraint when invoice_line_items table exists
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate tenant-module relationships
            $table->unique(['tenant_id', 'module_id']);

            // Indexes for performance
            $table->index(['tenant_id', 'is_active']);
            $table->index(['module_id', 'is_active']);
            $table->index('expires_at');
            $table->index('status');
            $table->index('billing_cycle');
            $table->index('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('tenant_modules');
    }
};