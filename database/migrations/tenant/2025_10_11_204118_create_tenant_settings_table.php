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
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();

            // POS Policies - Return & Void Sales
            $table->boolean('allow_cashier_void_last_sale')->default(true)
                ->comment('Permite a cajeros anular su última venta');
            $table->integer('cashier_void_time_limit_minutes')->default(5)
                ->comment('Límite de tiempo en minutos para anular ventas (cajeros)');
            $table->boolean('cashier_void_requires_same_day')->default(true)
                ->comment('Cajeros solo pueden anular ventas del mismo día');
            $table->boolean('cashier_void_requires_own_sale')->default(true)
                ->comment('Cajeros solo pueden anular sus propias ventas');

            // Future: otras políticas del negocio
            // $table->boolean('require_customer_for_sales')->default(false);
            // $table->decimal('max_discount_percentage', 5, 2)->default(0);
            // etc.

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
