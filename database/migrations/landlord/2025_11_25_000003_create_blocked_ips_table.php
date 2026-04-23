<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::connection('landlord')->create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique();
            $table->text('reason');
            $table->integer('violation_count')->default(1);
            $table->timestamp('blocked_at');
            $table->timestamp('blocked_until')->nullable()->comment('null = bloqueo permanente');
            $table->enum('block_type', ['temporary', 'permanent'])->default('temporary');
            $table->timestamps();

            $table->index(['ip_address', 'blocked_until']);
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('blocked_ips');
    }
};
