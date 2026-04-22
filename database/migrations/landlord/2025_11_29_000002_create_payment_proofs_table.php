<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('tenant_subscriptions')->onDelete('set null');
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->onDelete('set null');

            // Payment proof details
            $table->string('payment_method'); // bank_transfer, cash, mobile_money, other
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('payment_date');
            $table->text('reference_number')->nullable();
            $table->text('payer_name')->nullable();
            $table->text('notes')->nullable();

            // File attachments
            $table->json('file_paths')->nullable(); // Array of uploaded proof files
            $table->string('file_type')->nullable(); // Main file type for filtering
            $table->integer('total_file_size_mb')->default(0);

            // Status tracking
            $table->string('status')->default('pending'); // pending, under_review, approved, rejected
            $table->text('rejection_reason')->nullable();

            // Review tracking
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Additional data, verification hashes, etc.
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('payment_date');
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('payment_proofs');
    }
};