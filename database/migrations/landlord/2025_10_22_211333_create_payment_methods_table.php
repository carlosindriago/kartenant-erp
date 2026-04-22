<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Method details
            $table->string('type'); // card, bank_transfer, paypal, mercadopago
            $table->string('provider')->nullable(); // stripe, paypal, mercadopago
            $table->string('provider_id')->nullable(); // External payment method ID

            // Card details (encrypted/tokenized)
            $table->string('card_brand')->nullable(); // visa, mastercard, amex
            $table->string('card_last_four')->nullable();
            $table->string('card_exp_month')->nullable();
            $table->string('card_exp_year')->nullable();
            $table->string('card_holder_name')->nullable();

            // Status
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'is_default']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('payment_methods');
    }
};
