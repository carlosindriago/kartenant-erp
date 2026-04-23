<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::connection('landlord')->create('honeypot_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->index();
            $table->string('honeypot_field');
            $table->text('submitted_value');
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('honeypot_submissions');
    }
};
