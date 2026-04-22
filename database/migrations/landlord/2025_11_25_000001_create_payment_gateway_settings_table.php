<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('payment_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->string('driver_name', 50)->unique();
            $table->boolean('is_active')->default(false);
            $table->string('display_name');
            $table->jsonb('config')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('payment_gateway_settings');
    }
};
