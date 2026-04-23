<?php

use App\Providers\AppServiceProvider;
use App\Providers\EarlyBootServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\ModuleServiceProvider;
use App\Providers\TenantRouteServiceProvider;

return [
    EarlyBootServiceProvider::class,
    AppServiceProvider::class,
    EventServiceProvider::class,
    AdminPanelProvider::class,
    // App\Providers\Filament\AppPanelProvider::class, // DISABLED - Operation Ernesto Freedom - Migrating to custom Blade UI
    ModuleServiceProvider::class,
    TenantRouteServiceProvider::class, // Operation Ernesto Freedom - Dual Routing Infrastructure
];
