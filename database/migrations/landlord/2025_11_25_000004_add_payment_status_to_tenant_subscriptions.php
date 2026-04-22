<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->table('tenant_subscriptions', function (Blueprint $table) {
            $table->string('payment_status', 50)->default('pending')->after('status');
            // pending, pending_approval, paid, failed
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
