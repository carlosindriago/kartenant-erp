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
        Schema::connection('landlord')->table('tenants', function (Blueprint $table) {

            if (!Schema::connection('landlord')->hasColumn('tenants', 'locale')) {
                $table->string('locale', 10)->default('es')->after('timezone');

            }
            if (!Schema::connection('landlord')->hasColumn('tenants', 'locale')) {
                $table->string('currency', 10)->default('USD')->after('locale');

            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')->table('tenants', function (Blueprint $table) {
            $table->dropColumn(['locale', 'currency']);
        });
    }
};
