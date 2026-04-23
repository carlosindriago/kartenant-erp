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
        Schema::connection('landlord')->create('module_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('event_type'); // 'access', 'action', 'error', 'limit_reached', 'install', 'uninstall', 'configuration', 'usage'
            $table->string('feature_accessed')->nullable();
            $table->string('action_performed')->nullable();
            $table->text('description')->nullable();
            $table->json('usage_data')->nullable();
            $table->json('limits_data')->nullable();
            $table->decimal('usage_cost', 8, 4)->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->integer('memory_usage_mb')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->boolean('was_error')->default(false);
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_path')->nullable();
            $table->json('request_data')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['tenant_id', 'module_id'], 'idx_tenant_module_logs');
            $table->index('event_type');
            $table->index('created_at');
            $table->index(['tenant_id', 'created_at']);
            $table->index(['module_id', 'created_at']);
            $table->index('was_error');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('module_usage_logs');
    }
};
