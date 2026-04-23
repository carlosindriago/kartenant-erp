<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->table('subscription_plans', function (Blueprint $table) {
            // Add overage_tolerance column for enhanced soft limits configuration
            $table->integer('overage_tolerance')->default(0)->after('overage_percentage')
                ->comment('Tolerance percentage before triggering overage warnings or restrictions');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('overage_tolerance');
        });
    }
};
