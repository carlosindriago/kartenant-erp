<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Reporting;

use Illuminate\Support\ServiceProvider;

class ReportingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register services
        $this->app->singleton(\App\Modules\Reporting\Services\InventoryReportService::class);
        $this->app->singleton(\App\Modules\Reporting\Services\ABCAnalysisService::class);
        $this->app->singleton(\App\Modules\Reporting\Services\ProfitabilityService::class);
        $this->app->singleton(\App\Modules\Reporting\Services\TurnoverService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load views if needed
        $this->loadViewsFrom(__DIR__.'/Views', 'reporting');
    }
}
