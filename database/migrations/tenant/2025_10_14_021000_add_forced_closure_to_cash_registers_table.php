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
        if (! Schema::hasColumn('cash_registers', 'forced_closure')) {
            Schema::table('cash_registers', function (Blueprint $table) {
                $table->boolean('forced_closure')->default(false)->after('status');
                $table->unsignedBigInteger('forced_by_user_id')->nullable()->after('forced_closure');
                $table->text('forced_reason')->nullable()->after('forced_by_user_id');

                $table->index('forced_closure');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropIndex(['forced_closure']);
            $table->dropColumn(['forced_closure', 'forced_by_user_id', 'forced_reason']);
        });
    }
};
