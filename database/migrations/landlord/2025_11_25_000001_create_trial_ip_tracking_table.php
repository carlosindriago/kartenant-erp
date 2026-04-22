<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::connection('landlord')->create('trial_ip_tracking', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique()->comment('IP única que ha usado trial');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->timestamp('trial_started_at');
            $table->timestamp('trial_ends_at');
            $table->enum('status', ['active', 'expired', 'converted'])->default('active');
            $table->timestamps();
            
            $table->index('ip_address');
            $table->index('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('trial_ip_tracking');
    }
};
