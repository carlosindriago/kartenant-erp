<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->table('subscription_plans', function (Blueprint $table) {
            // Soft Limits Configuration
            $table->json('limits')->nullable()->after('features')->comment('Configurable limits for metrics like monthly_sales, users, storage_mb, products');
            $table->string('overage_strategy')->default('soft_limit')->after('limits')->comment('Strategy for handling overages: strict, soft_limit');
            $table->integer('overage_percentage')->default(20)->after('overage_strategy')->comment('Buffer zone percentage for soft limits');

            // Indexes for performance
            $table->index('overage_strategy');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->table('subscription_plans', function (Blueprint $table) {
            $table->dropIndex(['overage_strategy']);
            $table->dropColumn(['limits', 'overage_strategy', 'overage_percentage']);
        });
    }
};