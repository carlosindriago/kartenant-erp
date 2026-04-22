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
        // Logs de intentos sospechosos
        Schema::create('verification_security_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('event_type', 50); // rate_limit, invalid_hash, brute_force, bot_detected
            $table->text('details')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('referer', 500)->nullable();
            $table->integer('severity')->default(1); // 1-5: info, warning, alert, critical, emergency
            $table->timestamp('created_at');
            
            $table->index('ip_address');
            $table->index('tenant_id');
            $table->index('event_type');
            $table->index('created_at');
        });
        
        // Blacklist de IPs bloqueadas
        Schema::create('verification_ip_blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->unsignedBigInteger('tenant_id')->nullable(); // null = global block
            $table->string('reason', 200);
            $table->integer('offense_count')->default(1);
            $table->timestamp('blocked_at');
            $table->timestamp('expires_at')->nullable(); // null = permanent
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('ip_address');
            $table->index('tenant_id');
            $table->index(['is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_ip_blacklist');
        Schema::dropIfExists('verification_security_logs');
    }
};
