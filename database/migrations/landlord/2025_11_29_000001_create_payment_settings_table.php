<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('payment_settings', function (Blueprint $table) {
            $table->id();

            // Bank transfer details
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->string('bank_routing_number')->nullable();
            $table->string('bank_swift_code')->nullable();
            $table->string('bank_iban')->nullable();

            // Payment instructions
            $table->text('payment_instructions')->nullable();
            $table->text('payment_note')->nullable();

            // Business identification
            $table->string('business_name')->nullable();
            $table->string('business_tax_id')->nullable();
            $table->string('business_address')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('business_email')->nullable();

            // Currency and locale
            $table->string('default_currency')->default('USD');
            $table->string('locale')->default('es');

            // Payment processing settings
            $table->boolean('manual_approval_required')->default(true);
            $table->integer('approval_timeout_hours')->default(48);
            $table->boolean('auto_reminder_enabled')->default(true);
            $table->integer('reminder_interval_hours')->default(24);

            // File upload settings
            $table->integer('max_file_size_mb')->default(10);
            $table->json('allowed_file_types')->nullable();

            // Receipt and invoice settings
            $table->string('invoice_prefix')->default('INV-');
            $table->string('receipt_prefix')->default('REC-');
            $table->text('invoice_footer_text')->nullable();
            $table->text('receipt_footer_text')->nullable();

            // Legal and tax
            $table->decimal('tax_rate', 5, 4)->default(0);
            $table->boolean('tax_included')->default(false);
            $table->text('legal_terms')->nullable();
            $table->text('privacy_policy')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('default_currency');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('payment_settings');
    }
};
