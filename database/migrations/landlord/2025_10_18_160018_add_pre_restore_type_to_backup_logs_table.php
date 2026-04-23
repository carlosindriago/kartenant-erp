<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PostgreSQL doesn't support ALTER TYPE directly with Laravel
        // We need to use raw SQL to add new enum value
        DB::connection('landlord')->statement('
            ALTER TABLE backup_logs
            DROP CONSTRAINT IF EXISTS backup_logs_backup_type_check
        ');

        DB::connection('landlord')->statement("
            ALTER TABLE backup_logs
            ADD CONSTRAINT backup_logs_backup_type_check
            CHECK (backup_type IN ('daily', 'manual', 'pre-restore'))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove pre-restore option
        DB::connection('landlord')->statement('
            ALTER TABLE backup_logs
            DROP CONSTRAINT IF EXISTS backup_logs_backup_type_check
        ');

        DB::connection('landlord')->statement("
            ALTER TABLE backup_logs
            ADD CONSTRAINT backup_logs_backup_type_check
            CHECK (backup_type IN ('daily', 'manual'))
        ");
    }
};
