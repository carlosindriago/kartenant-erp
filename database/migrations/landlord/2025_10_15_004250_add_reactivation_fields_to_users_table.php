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
        Schema::table('users', function (Blueprint $table) {
            $table->text('reactivation_reason')->nullable()->after('deactivated_by');
            $table->timestamp('reactivated_at')->nullable()->after('reactivation_reason');
            $table->unsignedBigInteger('reactivated_by')->nullable()->after('reactivated_at');
            $table->string('reactivation_code', 6)->nullable()->after('reactivated_by');
            $table->timestamp('reactivation_code_expires_at')->nullable()->after('reactivation_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'reactivation_reason',
                'reactivated_at',
                'reactivated_by',
                'reactivation_code',
                'reactivation_code_expires_at'
            ]);
        });
    }
};
