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

class MakeSpatieTenantCurrent
{
    public function handle(Request $request, Closure $next)
    {
        // CRITICAL SECURITY: Block Laravel default authentication routes that bypass 2FA
        // BUT allow tenant authentication routes when accessed from tenant subdomains
        $blockedPaths = ['login', 'register', 'password/request', 'password/reset', 'password/email'];
        $requestPath = $request->path();
        $host = $request->getHost();
        $hostParts = explode('.', $host);
        $isTenantSubdomain = count($hostParts) >= 3;

        // Only block authentication paths on apex domain or when not in tenant context
        if ((in_array($requestPath, $blockedPaths) || str_starts_with($requestPath, 'password/')) && ! $isTenantSubdomain) {
            \Log::alert('Authentication bypass attempt detected and blocked', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $requestPath,
                'url' => $request->fullUrl(),
                'host' => $host,
                'method' => $request->method(),
                'timestamp' => now()->toISOString(),
                'is_tenant_subdomain' => $isTenantSubdomain,
            ]);

            abort(404);
        }

        // NOTE: This middleware is ONLY applied in AppPanelProvider
        // It does NOT run on admin panel routes

        // If a tenant is already current, continue
        $containerKey = config('multitenancy.current_tenant_container_key', 'currentTenant');
        if (app()->bound($containerKey) && app($containerKey)) {
            return $next($request);
        }

        // --- BYPASS OPEN-CORE (MODO STANDALONE) ---
        if (config('app.mode') === 'standalone') {
            // Buscamos o creamos el Tenant de la comunidad (ID 1)
            $tenant = \App\Models\Tenant::firstOrCreate(
                ['id' => 1],
                [
                    'name' => 'Comunidad Open Source',
                    // Añade aquí otros campos obligatorios que tenga tu tabla tenants (ej. domain)
                    'domain' => 'localhost',
                ]
            );

            // Lo activamos a la fuerza
            $tenant->makeCurrent();

            return $next($request);
        }
        // ------------------------------------------

        // Resolve tenant by domain from the request host
        $host = (string) $request->getHost();
        $parts = explode('.', $host);
        // Apex or no-subdomain host? Avoid applying tenancy here (e.g., kartenant.test)
        if (count($parts) < 3) {
            // If the request is trying to access the tenant panel path on apex, guide the user back gracefully.
            if ($request->is('app') || $request->is('app/*')) {
                $scheme = $request->isSecure() ? 'https' : 'http';
                $target = sprintf('%s://%s/?tenant_required=1', $scheme, $host);

                return redirect()->away($target);
            }

            return $next($request);
        }
        $tenant = Tenant::query()->where('domain', $host)->first();

        // Fallback: if not found, try resolving by subdomain slug (first label)
        if (! $tenant) {
            $slug = $parts[0];
            $tenant = Tenant::query()->where('domain', $slug)->first();
        }

        if ($tenant) {
            // Make this tenant current for Spatie Multitenancy
            $tenant->makeCurrent();

            // Force purge the tenant connection to ensure fresh connection with updated config
            \Illuminate\Support\Facades\DB::purge('tenant');
        } else {
            // No tenant found on subdomain: redirect to apex with a friendly flag
            $apex = implode('.', array_slice($parts, 1));
            $scheme = $request->isSecure() ? 'https' : 'http';
            $slug = $parts[0] ?? '';
            $target = sprintf('%s://%s/?tenant_not_found=1&t=%s', $scheme, $apex, urlencode((string) $slug));

            return redirect()->away($target);
        }

        return $next($request);
    }
}
