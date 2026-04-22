<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('tenant_subscriptions')->onDelete('set null');
            $table->string('gateway_driver', 50);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 50); // pending, approved, rejected, completed, failed
            $table->string('transaction_id')->nullable();
            $table->text('proof_of_payment')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index('gateway_driver');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('payment_transactions');
    }
};
