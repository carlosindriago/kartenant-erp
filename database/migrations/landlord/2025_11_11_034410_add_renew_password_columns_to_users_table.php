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
        Schema::connection('landlord')->table('users', function (Blueprint $table) {
            // Columnas para el plugin yebor974/filament-renew-password
            $table->boolean('force_renew_password')->default(false)->after('must_change_password');
            $table->timestamp('last_password_change_at')->nullable()->after('force_renew_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')->table('users', function (Blueprint $table) {
            $table->dropColumn(['force_renew_password', 'last_password_change_at']);
        });
    }
};
