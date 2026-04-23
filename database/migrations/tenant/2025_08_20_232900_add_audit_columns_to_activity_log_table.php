<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('guard', 50)->nullable()->index();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('route', 255)->nullable();
            $table->string('method', 10)->nullable();
            $table->index('created_at', 'activity_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // Drop index first if it exists
            try {
                $table->dropIndex('activity_created_idx');
            } catch (Throwable $e) {
                // ignore
            }

            $table->dropColumn([
                'guard',
                'ip',
                'user_agent',
                'route',
                'method',
            ]);
        });
    }
};
