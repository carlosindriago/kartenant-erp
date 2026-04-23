<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPlan;
use Illuminate\Console\Command;

class MigrateSubscriptionLimits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emporio:migrate-subscription-limits {--force : Force migration even if limits already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate legacy subscription limit columns to unified JSON structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Migrating Subscription Limits to Unified JSON Structure...');
        $this->newLine();

        $plans = SubscriptionPlan::all();
        $migratedCount = 0;
        $skippedCount = 0;

        if ($plans->isEmpty()) {
            $this->warn('No subscription plans found in the database.');

            return 0;
        }

        $this->withProgressBar($plans, function (SubscriptionPlan $plan) use (&$migratedCount, &$skippedCount) {
            if (! $this->option('force') && $plan->hasConfigurableLimits()) {
                $skippedCount++;

                return;
            }

            $result = $plan->migrateLegacyLimits();
            if ($result) {
                $migratedCount++;
            }
        });

        $this->newLine();
        $this->info('✅ Migration Complete!');
        $this->line("📊 Plans migrated: {$migratedCount}");
        $this->line("⏭️  Plans skipped: {$skippedCount}");
        $this->newLine();

        if ($migratedCount > 0) {
            $this->info('🎯 All plans now use the unified limits configuration.');
            $this->line('You can now use the new form fields to manage subscription plan limits.');
        }

        return 0;
    }
}
