<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateDatabase extends Command
{
    protected $signature = 'db:create {name : The name of the database to create}';

    protected $description = 'Create a new PostgreSQL database';

    public function handle()
    {
        $databaseName = $this->argument('name');

        try {
            // Create the database using the landlord connection
            $databaseNameStr = (string) $databaseName;
            DB::connection('landlord')->statement("CREATE DATABASE {$databaseNameStr}");

            $this->info("✅ Database '{$databaseNameStr}' created successfully");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $databaseNameStrCatch = (string) $databaseName;
            $this->error("❌ Failed to create database '{$databaseNameStrCatch}': ".$e->getMessage());

            return self::FAILURE;
        }
    }
}
