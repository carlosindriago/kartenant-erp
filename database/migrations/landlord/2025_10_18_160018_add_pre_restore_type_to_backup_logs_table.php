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
        if (DB::connection('landlord')->getDriverName() === 'pgsql') {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection('landlord')->getDriverName() === 'pgsql') {
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
    }
};
