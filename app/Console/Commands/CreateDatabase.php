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

        if (! is_string($databaseName)) {
            $this->error('The name argument must be a string.');
            return self::FAILURE;
        }

        try {
            // Create the database using the landlord connection
            DB::connection('landlord')->statement("CREATE DATABASE {$databaseName}");

            $this->info("✅ Database '{$databaseName}' created successfully");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to create database '{$databaseName}': ".$e->getMessage());

            return self::FAILURE;
        }
    }
}
