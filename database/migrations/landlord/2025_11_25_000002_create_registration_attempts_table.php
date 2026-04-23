<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::connection('landlord')->create('registration_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->index();
            $table->string('email')->nullable();
            $table->string('domain_attempted')->nullable();
            $table->integer('attempt_count')->default(1);
            $table->timestamp('last_attempt_at');
            $table->string('user_agent', 500)->nullable();
            $table->boolean('captcha_passed')->default(false);
            $table->decimal('captcha_score', 3, 2)->nullable()->comment('Score para reCAPTCHA v3');
            $table->string('blocked_reason')->nullable();
            $table->timestamps();

            $table->index(['ip_address', 'last_attempt_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('registration_attempts');
    }
};
