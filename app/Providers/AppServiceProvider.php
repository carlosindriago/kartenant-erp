<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Providers;

use App\Models\Tenant;
use App\Modules\Inventory\Models\Product;
use App\Modules\POS\Models\Sale;
use App\Modules\POS\Models\SaleReturn;
use App\Observers\ProductObserver;
use App\Observers\SaleObserver;
use App\Observers\SaleReturnObserver;
use App\Observers\TenantObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Configure Spatie Activitylog to use landlord connection and custom model
        config()->set('activitylog.activity_model', \App\Models\Activity::class);
        config()->set('activitylog.database_connection', 'landlord');
        config()->set('activitylog.delete_records_older_than_days', 90);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load landlord migrations from dedicated directory
        $this->loadMigrationsFrom([
            database_path('migrations/landlord'),
        ]);

        // "Contratamos" a nuestro observador aquí
        Tenant::observe(TenantObserver::class);
        Product::observe(ProductObserver::class);
        Sale::observe(SaleObserver::class);
        SaleReturn::observe(SaleReturnObserver::class);
    }
}
