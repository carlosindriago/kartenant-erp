<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Event data
            $table->string('event_type')->index(); // login, logout, feature_used, page_view, etc.
            $table->string('event_category')->index(); // user, tenant, feature, system
            $table->string('event_name'); // inventory.create_product, pos.create_sale, etc.
            $table->text('event_description')->nullable();

            // Context data
            $table->json('properties')->nullable(); // Additional data (feature details, metadata)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable()->index();

            // Performance metrics
            $table->integer('duration_ms')->nullable(); // Time taken for the event
            $table->string('status')->nullable(); // success, error, warning

            $table->timestamp('created_at')->index();

            // Indexes for common queries
            $table->index(['tenant_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            $table->index(['event_category', 'created_at']);
        });

        // Create index for date-based queries
        DB::connection('landlord')->statement('CREATE INDEX analytics_events_created_at_date_idx ON analytics_events ((created_at::date))');
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('analytics_events');
    }
};
