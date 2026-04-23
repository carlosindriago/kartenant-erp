<?php

namespace App\Providers;

use App\Console\Commands\UsageBillingProcessCommand;
use App\Console\Commands\UsageRecalculateCommand;
use App\Console\Commands\UsageResetCommand;
use App\Console\Commands\UsageSyncCommand;
use App\Console\Commands\UsageTestAlertCommand;
use App\Http\Middleware\UsageLimitMiddleware;
use App\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\POS\Models\Sale;
use App\Observers\StorageUsageObserver;
use App\Observers\UsageTrackingObserver;
use App\Services\BillingService;
use App\Services\TenantUsageService;
use App\Services\UsageAlertService;
use App\Services\UsageBillingIntegrationService;
use App\View\Components\UsageWarningBanner;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class UsageTrackingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services as singletons for performance
        $this->app->singleton(TenantUsageService::class, function ($app) {
            return new TenantUsageService;
        });

        $this->app->singleton(UsageAlertService::class, function ($app) {
            return new UsageAlertService;
        });

        $this->app->singleton(UsageBillingIntegrationService::class, function ($app) {
            return new UsageBillingIntegrationService(
                $app->make(TenantUsageService::class),
                $app->make(BillingService::class)
            );
        });

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/usage-limits.php',
            'usage-limits'
        );
    }

    public function boot(): void
    {
        // Register observers
        $this->registerObservers();

        // Register event listeners
        $this->registerEventListeners();

        // Register middleware
        $this->registerMiddleware();

        // Register console commands
        $this->registerCommands();

        // Load views
        $this->loadViewsFrom(
            __DIR__.'/../../resources/views/components/usage',
            'usage'
        );

        // Register Blade components
        $this->registerBladeComponents();

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/usage-limits.php' => config_path('usage-limits.php'),
            ], 'usage-limits-config');

            $this->publishes([
                __DIR__.'/../../resources/views' => resource_path('views/vendor/usage'),
            ], 'usage-limits-views');
        }

        // Schedule periodic tasks
        $this->scheduleTasks();
    }

    private function registerObservers(): void
    {
        // Register usage tracking observers
        $usageObserver = $this->app->make(UsageTrackingObserver::class);

        // Product observer (if exists)
        if (class_exists('\App\Modules\Inventory\Models\Product')) {
            Product::observe($usageObserver);
        }

        // User observer
        User::observe($usageObserver);

        // Sale observer (if exists)
        if (class_exists('\App\Modules\POS\Models\Sale')) {
            Sale::observe($usageObserver);
        }

        // Storage observer
        $storageObserver = $this->app->make(StorageUsageObserver::class);

        // Register file system events
        $this->registerFileSystemEvents($storageObserver);
    }

    private function registerEventListeners(): void
    {
        // Listen for tenant events
        Event::listen('tenant.created', function ($tenant) {
            // Initialize usage tracking for new tenant
            app(TenantUsageService::class)->getCurrentUsage($tenant->id, true);
        });

        Event::listen('tenant.plan.changed', function ($tenant, $oldPlan, $newPlan) {
            // Handle plan changes
            app(UsageBillingIntegrationService::class)->handlePlanUpgrade($tenant, $newPlan);
        });

        // Listen for subscription events
        Event::listen('subscription.created', function ($subscription) {
            // Initialize usage tracking for new subscription
            $usageService = app(TenantUsageService::class);
            $usageService->getCurrentUsage($subscription->tenant_id, true);
        });

        // Listen for file events
        Event::listen('file.uploaded', function ($path, $size, $tenantId) {
            app(StorageUsageObserver::class)->fileUploaded($path, $size, $tenantId);
        });

        Event::listen('file.deleted', function ($path, $tenantId) {
            app(StorageUsageObserver::class)->fileDeleted($path, $tenantId);
        });
    }

    private function registerMiddleware(): void
    {
        // Register middleware alias
        $router = $this->app['router'];

        $router->aliasMiddleware('usage.limits', UsageLimitMiddleware::class);
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                UsageResetCommand::class,
                UsageRecalculateCommand::class,
                UsageTestAlertCommand::class,
                UsageSyncCommand::class,
                UsageBillingProcessCommand::class,
            ]);
        }
    }

    private function registerBladeComponents(): void
    {
        // Register usage banner component
        $this->loadViewComponentsAs('usage', [
            'warning-banner' => UsageWarningBanner::class,
        ]);
    }

    private function registerFileSystemEvents(StorageUsageObserver $observer): void
    {
        // This would require integration with your file storage system
        // For now, we'll rely on manual calls from your file upload handlers

        // Example: If you're using Laravel's file events
        // Event::listen('illuminate.queue.stopping', function () use ($observer) {
        //     // Process any pending storage tracking
        // });
    }

    private function scheduleTasks(): void
    {
        if ($this->app->runningInConsole()) {
            $schedule = $this->app->make(Schedule::class);

            // Reset monthly counters (1st day of month at 00:05)
            $schedule->command('usage:reset-monthly')
                ->cron('5 0 1 * *')
                ->description('Reset monthly usage counters');

            // Process pending alerts every 5 minutes
            $schedule->command('usage:process-alerts')
                ->everyFiveMinutes()
                ->description('Process pending usage alerts');

            // Synchronize Redis counters every hour
            $schedule->command('usage:sync')
                ->hourly()
                ->description('Synchronize Redis counters with database');

            // Process billing cycle transitions (daily at 02:00)
            $schedule->command('usage:process-billing')
                ->cron('0 2 * * *')
                ->description('Process monthly billing cycles');

            // Clean up old metrics logs (weekly)
            $schedule->command('usage:cleanup')
                ->weekly()
                ->description('Clean up old usage metrics logs');

            // Recalculate usage for discrepancies (daily at 03:00)
            $schedule->command('usage:recalculate')
                ->cron('0 3 * * *')
                ->description('Recalculate usage for discrepancies');

            // Test alert system (development only)
            if (config('usage-limits.development.test_alerts', false)) {
                $schedule->command('usage:test-alert')
                    ->daily()
                    ->description('Test alert system (development only)');
            }
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            TenantUsageService::class,
            UsageAlertService::class,
            UsageBillingIntegrationService::class,
        ];
    }
}
