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

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class EarlyBootServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // This runs very early in the boot process, before other service providers
        // Only check for installation file to avoid database connection issues
        if (! $this->isInstalled()) {
            // Completely disable multitenancy during installation
            config([
                'multitenancy.tenant_finder' => null,
                'multitenancy.switch_tenant_tasks' => [],
                'multitenancy.tenant_model' => null,
            ]);

            // Change session driver to array during installation to avoid database dependency
            config([
                'session.driver' => 'array',
                'cache.default' => 'array',
                'queue.default' => 'sync', // Use sync queue during installation
            ]);

            // Mark multitenancy as disabled for other parts of the app
            $this->app->instance('multitenancy.disabled', true);

            // Disable multitenancy queue listeners
            $this->app->bind(\Spatie\Multitenancy\Contracts\IsTenant::class, function () {
                return null;
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Disable multitenancy event listeners during installation
        if (! $this->isInstalled()) {
            // Remove multitenancy queue listeners
            $this->app['events']->forget('Illuminate\Queue\Events\JobProcessing');
            $this->app['events']->forget('Illuminate\Queue\Events\JobProcessed');
            $this->app['events']->forget('Illuminate\Queue\Events\JobFailed');
        }
    }

    private function isInstalled(): bool
    {
        return File::exists(base_path('.installed'));
    }
}
