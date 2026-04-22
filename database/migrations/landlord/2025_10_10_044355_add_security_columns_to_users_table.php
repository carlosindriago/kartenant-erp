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
            if (!Schema::hasColumn('users', 'email_2fa_code')) {
                $table->string('email_2fa_code', 6)->nullable()->after('two_factor_confirmed_at');
            }
            if (!Schema::hasColumn('users', 'email_2fa_expires_at')) {
                $table->timestamp('email_2fa_expires_at')->nullable()->after('email_2fa_code');
            }
            if (!Schema::hasColumn('users', 'password_change_code')) {
                $table->string('password_change_code', 6)->nullable()->after('email_2fa_expires_at');
            }
            if (!Schema::hasColumn('users', 'password_change_code_expires_at')) {
                $table->timestamp('password_change_code_expires_at')->nullable()->after('password_change_code');
            }
            if (!Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('password_change_code_expires_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_2fa_code',
                'email_2fa_expires_at',
                'password_change_code',
                'password_change_code_expires_at',
                'password_changed_at',
            ]);
        });
    }
};
