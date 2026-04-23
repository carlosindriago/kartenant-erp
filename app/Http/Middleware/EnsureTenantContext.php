<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Tenant Context Middleware - Operation Ernesto Freedom
 *
 * This middleware ensures tenant context is properly established for custom tenant routes.
 * It works IN PARALLEL with the existing MakeSpatieTenantCurrent middleware and provides
 * additional safety checks for the new dual routing system.
 *
 * IMPORTANT: This middleware does NOT replace existing functionality.
 * It enhances and validates tenant context for the /tenant/* routes.
 */
final class EnsureTenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if we're on a tenant subdomain
        $host = $request->getHost();
        $parts = explode('.', $host);

        // Skip tenant context for non-tenant subdomains
        // This allows apex domain and admin routes to work normally
        if (count($parts) < 3) {
            // If accessing /tenant routes on apex domain, provide helpful error
            if ($request->is('tenant*')) {
                return $this->tenantRequiredError($request, $host);
            }

            return $next($request);
        }

        // Try to resolve tenant by domain (exact match first)
        $tenant = Tenant::query()->where('domain', $host)->first();

        // Fallback: try resolving by subdomain slug
        if (! $tenant) {
            $slug = $parts[0];
            $tenant = Tenant::query()->where('domain', $slug)->first();
        }

        // If tenant not found, redirect to apex with error
        if (! $tenant) {
            return $this->tenantNotFoundError($request, $host, $parts[0] ?? '');
        }

        // Validate tenant status
        if ($tenant->status !== Tenant::STATUS_ACTIVE) {
            return $this->tenantInactiveError($request, $tenant);
        }

        // Ensure tenant is current in Spatie Multitenancy
        $containerKey = config('multitenancy.current_tenant_container_key', 'currentTenant');

        // If no tenant is current, make this tenant current
        if (! app()->bound($containerKey) || ! app($containerKey)) {
            $tenant->makeCurrent();

            // Force purge tenant connection to ensure fresh connection
            \Illuminate\Support\Facades\DB::purge('tenant');
        }

        // Verify the current tenant matches the request subdomain
        $currentTenant = app($containerKey);
        if ($currentTenant && $currentTenant->id !== $tenant->id) {
            // Security: Prevent tenant context switching attacks
            \Log::warning('Tenant context mismatch detected', [
                'request_host' => $host,
                'expected_tenant_id' => $tenant->id,
                'current_tenant_id' => $currentTenant->id,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);

            // Make the correct tenant current
            $tenant->makeCurrent();
            \Illuminate\Support\Facades\DB::purge('tenant');
        }

        // Add tenant context to request for downstream use
        $request->attributes->set('current_tenant', $tenant);
        $request->attributes->set('tenant_subdomain', $parts[0]);

        // Log tenant context for debugging
        \Log::debug('Tenant context established', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'request_path' => $request->path(),
            'request_host' => $host,
        ]);

        return $next($request);
    }

    /**
     * Handle case where tenant is required but not on tenant subdomain.
     */
    private function tenantRequiredError(Request $request, string $host): Response
    {
        $scheme = $request->isSecure() ? 'https' : 'http';

        // If it's a development environment, show helpful error
        if (app()->environment('local', 'testing')) {
            return response()->view('errors.tenant-required', [
                'host' => $host,
                'scheme' => $scheme,
                'requested_path' => $request->path(),
            ], 400);
        }

        // In production, redirect to apex with error flag
        $target = sprintf('%s://%s/?tenant_required=1&path=%s',
            $scheme,
            $host,
            urlencode($request->path())
        );

        return redirect()->away($target);
    }

    /**
     * Handle case where tenant is not found.
     */
    private function tenantNotFoundError(Request $request, string $host, string $slug): Response
    {
        $apex = implode('.', array_slice(explode('.', $host), 1));
        $scheme = $request->isSecure() ? 'https' : 'http';

        // Log the attempted access for security monitoring
        \Log::warning('Tenant not found for subdomain', [
            'subdomain' => $slug,
            'host' => $host,
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // In development, show helpful error page
        if (app()->environment('local', 'testing')) {
            return response()->view('errors.tenant-not-found', [
                'subdomain' => $slug,
                'host' => $host,
                'requested_path' => $request->path(),
            ], 404);
        }

        // In production, redirect to apex with error flag
        $target = sprintf('%s://%s/?tenant_not_found=1&t=%s',
            $scheme,
            $apex,
            urlencode($slug)
        );

        return redirect()->away($target);
    }

    /**
     * Handle case where tenant is inactive.
     */
    private function tenantInactiveError(Request $request, Tenant $tenant): Response
    {
        \Log::warning('Access attempt to inactive tenant', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'host' => $request->getHost(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        // In development, show detailed error
        if (app()->environment('local', 'testing')) {
            return response()->view('errors.tenant-inactive', [
                'tenant' => $tenant,
                'requested_path' => $request->path(),
            ], 403);
        }

        // In production, show generic error
        return response()->view('errors.tenant-inactive', [
            'tenant' => null, // Hide tenant details in production
            'requested_path' => $request->path(),
        ], 403);
    }
}
