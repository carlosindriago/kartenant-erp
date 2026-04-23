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

use App\Modules\Reporting\Services\ABCAnalysisService;
use App\Modules\Reporting\Services\InventoryReportService;
use App\Modules\Reporting\Services\ProfitabilityService;
use App\Modules\Reporting\Services\TurnoverService;
use Illuminate\Support\ServiceProvider;

class ReportingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register services
        $this->app->singleton(InventoryReportService::class);
        $this->app->singleton(ABCAnalysisService::class);
        $this->app->singleton(ProfitabilityService::class);
        $this->app->singleton(TurnoverService::class);
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
