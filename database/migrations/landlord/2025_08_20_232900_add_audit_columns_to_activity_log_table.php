<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable();
                $table->string('guard', 50)->nullable()->index();
                $table->string('ip', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('route', 255)->nullable();
                $table->string('method', 10)->nullable();
                $table->index(['tenant_id', 'created_at'], 'activity_tenant_created_idx');
            });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))
            ->table(config('activitylog.table_name'), function (Blueprint $table) {
                // Drop composite index first if it exists
                try {
                    $table->dropIndex('activity_tenant_created_idx');
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->dropColumn([
                    'tenant_id',
                    'guard',
                    'ip',
                    'user_agent',
                    'route',
                    'method',
                ]);
            });
    }
};
