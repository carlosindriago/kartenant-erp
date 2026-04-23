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
        Schema::connection('tenant')->table('cash_registers', function (Blueprint $table) {
            if (! Schema::connection('tenant')->hasColumn('cash_registers', 'forced_closure')) {
                $table->boolean('forced_closure')->default(false)->after('closing_notes');
            }

            if (! Schema::connection('tenant')->hasColumn('cash_registers', 'forced_by_user_id')) {
                $table->unsignedBigInteger('forced_by_user_id')->nullable()->after('closing_notes');
            }

            if (! Schema::connection('tenant')->hasColumn('cash_registers', 'forced_reason')) {
                $table->text('forced_reason')->nullable()->after('closing_notes');
            }

            // No podemos crear foreign key porque users está en landlord DB
            // Solo guardamos el ID como referencia
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('cash_registers', function (Blueprint $table) {
            $table->dropColumn(['forced_closure', 'forced_by_user_id', 'forced_reason']);
        });
    }
};
