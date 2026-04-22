<?php

return [
    App\Providers\EarlyBootServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    // App\Providers\Filament\AppPanelProvider::class, // DISABLED - Operation Ernesto Freedom - Migrating to custom Blade UI
    App\Providers\ModuleServiceProvider::class,
    App\Providers\TenantRouteServiceProvider::class, // Operation Ernesto Freedom - Dual Routing Infrastructure
];
