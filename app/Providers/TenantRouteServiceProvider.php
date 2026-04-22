<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\EnsureTenantContext;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * Tenant Route Service Provider - Operation Ernesto Freedom
 *
 * This service provider manages the custom tenant routing infrastructure
 * that runs in parallel with the existing Filament panel system.
 *
 * Key Responsibilities:
 * - Load custom tenant routes (/tenant/*)
 * - Configure Livewire for tenant context persistence
 * - Maintain compatibility with existing system
 * - Enable dual routing (/app for Filament, /tenant for custom)
 */
final class TenantRouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register tenant-specific services if needed
        $this->registerTenantServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerTenantRoutes();
        $this->configureLivewireForTenants();
    }

    /**
     * Register tenant-specific services.
     */
    private function registerTenantServices(): void
    {
        // Bind tenant-specific services here if needed
        // This allows for dependency injection and testing
    }

    /**
     * Register the custom tenant routes.
     *
     * These routes work IN PARALLEL with existing Filament routes.
     * The /tenant prefix prevents conflicts with /app routes.
     */
    private function registerTenantRoutes(): void
    {
        Route::middleware(['web'])
            ->group(base_path('routes/tenant.php'));

        // Optional: Register additional route files for modular organization
        // For example: Route::middleware(['web'])->group(base_path('routes/tenant-api.php'));
    }

    /**
     * Configure Livewire for tenant context persistence.
     *
     * This ensures that all Livewire requests maintain the proper
     * tenant database connection and context while preserving
     * compatibility with existing Filament functionality.
     */
    private function configureLivewireForTenants(): void
    {
        // IMPORTANT: We DO NOT modify global Livewire configuration here
        // to avoid conflicts with Filament's existing setup

        // The existing MakeSpatieTenantCurrent middleware in bootstrap/app.php
        // already handles tenant context for all web requests including Livewire

        // Our EnsureTenantContext middleware will be used explicitly
        // in custom tenant route definitions where additional validation is needed

        // For now, we rely on the existing tenant context setup
        // Custom Livewire update routes will be handled per-component as needed
    }

    /**
     * Configure custom Livewire components for tenant routes.
     *
     * This method will be used in future phases to register
     * tenant-specific Livewire components with proper context.
     */
    private function configureTenantLivewireComponents(): void
    {
        // Future: Register tenant-specific Livewire components
        // Example: Livewire::component('tenant.dashboard', TenantDashboard::class);

        // Components will be registered here as we implement them
        // in subsequent phases of Operation Ernesto Freedom
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            // Declare services this provider provides for optimization
        ];
    }
}