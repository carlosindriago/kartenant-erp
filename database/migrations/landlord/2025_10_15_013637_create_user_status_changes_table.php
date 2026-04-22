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
        Schema::create('user_status_changes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Usuario afectado
            $table->string('action'); // 'deactivated' o 'activated'
            $table->text('reason'); // Razón del cambio
            $table->unsignedBigInteger('changed_by'); // Quien hizo el cambio
            $table->timestamp('changed_at'); // Cuándo se hizo
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('action');
            $table->index('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_status_changes');
    }
};
