<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Http\Middleware;

use App\Permissions\LandlordPermissionRegistrar;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class UseLandlordPermissionRegistrar
{
    public function handle(Request $request, Closure $next)
    {
        // Only bind a no-op registrar for admin context to avoid tenant permission queries.
        // Livewire posts to /livewire/update, so we also check Referer header for /admin.
        $referer = (string) $request->headers->get('referer', '');
        $isAdminContext = $request->is('admin*') || str_contains($referer, '/admin');
        if ($isAdminContext) {
            // CRITICAL: Force default DB connection to landlord for all queries
            // This prevents accidental use of 'tenant' connection
            \DB::setDefaultConnection('landlord');
            config(['database.default' => 'landlord']);
            // Let the container resolve constructor deps for our subclass
            app()->bind(PermissionRegistrar::class, LandlordPermissionRegistrar::class);
            // Use a separate cache key for landlord permissions to prevent mixing with tenant cache
            config()->set('permission.cache.key', 'spatie.permission.cache.landlord');
            // Force landlord models and guard for Spatie in admin context
            config()->set('permission.models.role', \App\Models\Landlord\Role::class);
            config()->set('permission.models.permission', \App\Models\Landlord\Permission::class);
            config()->set('permission.default_guard', 'superadmin');
            // Ensure the default auth guard resolves to superadmin for model guard inference
            config()->set('auth.defaults.guard', 'superadmin');
            // Ensure Gate is (re)registered with our registrar for this request
            try {
                $permissionRegistrar = app(\Spatie\Permission\PermissionRegistrar::class);
                $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);

                // Clear any cached permissions to ensure fresh landlord permissions
                $permissionRegistrar->forgetCachedPermissions();

                // Register permissions with the gate for proper authorization
                $permissionRegistrar->registerPermissions($gate);

            } catch (\Exception $e) {
                // If registration fails (e.g., tenant context activation), continue anyway
                // The admin user will still have access via is_super_admin check
                \Log::warning('Failed to register landlord permissions in UseLandlordPermissionRegistrar', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        return $next($request);
    }
}
