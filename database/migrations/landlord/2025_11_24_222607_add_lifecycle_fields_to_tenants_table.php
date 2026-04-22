<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::connection('landlord')->table('tenants', function (Blueprint $table) {
            if (!Schema::connection('landlord')->hasColumn('tenants', 'status')) {
                $table->string('status', 20)->default('active')->after('database');
            }

            if (!Schema::connection('landlord')->hasColumn('tenants', 'deleted_at')) {
                $table->softDeletes();
            }

            // Add indexes for performance (only if they don't exist)
            if (!Schema::connection('landlord')->hasIndex('tenants', 'tenants_status_index')) {
                $table->index('status');
            }

            if (!Schema::connection('landlord')->hasIndex('tenants', 'tenants_status_deleted_at_index')) {
                $table->index(['status', 'deleted_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')->table('tenants', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('status');

            // Drop indexes if they exist
            $table->dropIndex(['status']);
            $table->dropIndex(['status', 'deleted_at']);
        });
    }
};