<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('tenant_subscription_id')->nullable()->constrained('tenant_subscriptions')->onDelete('set null');

            // Invoice details
            $table->string('invoice_number')->unique();
            $table->string('status')->default('pending'); // pending, paid, failed, cancelled, refunded
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency')->default('USD');

            // Payment details
            $table->string('payment_method')->nullable();
            $table->string('payment_provider')->nullable();
            $table->string('provider_payment_id')->nullable(); // Stripe charge ID, PayPal transaction ID
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_date')->nullable();

            // Billing info
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('tax_id')->nullable(); // RFC, CUIT, etc.

            // Items (JSON for flexibility)
            $table->json('items')->nullable(); // Line items

            // Notes
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();

            // PDF
            $table->string('pdf_path')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('invoice_number');
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('invoices');
    }
};
