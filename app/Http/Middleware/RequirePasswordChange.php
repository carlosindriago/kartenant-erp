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

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Skip if not authenticated
        if (! $user) {
            return $next($request);
        }

        // Skip if already on password change page (prevent redirect loop)
        $currentPath = $request->path();
        $currentUrl = $request->url();

        // Multiple checks to ensure we detect the change-password page
        if (str_contains($currentPath, 'change-password') ||
            str_contains($currentUrl, 'change-password') ||
            $request->routeIs('filament.app.pages.change-password')) {
            return $next($request);
        }

        // Skip if on logout route
        if ($request->routeIs('filament.app.auth.logout') || str_contains($currentPath, 'logout')) {
            return $next($request);
        }

        // Skip Livewire requests (prevent interference with AJAX)
        if ($request->is('livewire/*') || $request->header('X-Livewire')) {
            return $next($request);
        }

        // Redirect to password change page if required
        if ($user->needsPasswordChange()) {
            // Get tenant from route parameter (more reliable than Filament::getTenant() in middleware)
            $tenantSlug = $request->route('tenant');

            // Fallback: try to get from current tenant context (Spatie Multitenancy)
            if (! $tenantSlug) {
                $currentTenant = Tenant::current();
                if ($currentTenant) {
                    $tenantSlug = $currentTenant->domain;
                }
            }

            // Last fallback: extract from subdomain
            if (! $tenantSlug) {
                $host = $request->getHost();
                $parts = explode('.', $host);
                if (count($parts) > 2) {
                    $tenantSlug = $parts[0];
                }
            }

            if (! $tenantSlug) {
                // If still no tenant, something is wrong - redirect to main app
                return redirect('/app');
            }

            return redirect()->route('filament.app.pages.change-password', ['tenant' => $tenantSlug]);
        }

        return $next($request);
    }
}
