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
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant API Middleware
 *
 * Handles multi-tenancy for API requests using X-Tenant-ID header.
 * This middleware:
 * 1. Reads X-Tenant-ID header from request
 * 2. Finds and validates the tenant
 * 3. Makes the tenant current for the request
 * 4. Returns error if tenant not found or inactive
 */
class TenantApiMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get X-Tenant-ID from header
        $tenantId = $request->header('X-Tenant-ID');

        // Check if tenant ID is provided
        if (! $tenantId) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TENANT_ID_MISSING',
                    'message' => 'Header X-Tenant-ID es requerido',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        }

        // Find tenant by ID
        $tenant = Tenant::find($tenantId);

        // Check if tenant exists
        if (! $tenant) {
            Log::warning('[TenantApiMiddleware] Tenant not found', [
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TENANT_NOT_FOUND',
                    'message' => 'Tenant no encontrado',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        }

        // Check if tenant is active
        if ($tenant->status !== 'active') {
            Log::warning('[TenantApiMiddleware] Tenant is inactive', [
                'tenant_id' => $tenantId,
                'tenant_name' => $tenant->name,
                'tenant_status' => $tenant->status,
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TENANT_INACTIVE',
                    'message' => 'Este tenant está inactivo. Contacte al administrador.',
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 403);
        }

        // Check if authenticated user belongs to this tenant
        $user = auth()->user();
        if ($user) {
            $belongsToTenant = $user->tenants()->where('tenants.id', $tenant->id)->exists();

            if (! $belongsToTenant) {
                Log::warning('[TenantApiMiddleware] User does not belong to tenant', [
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenant->name,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => 'No tienes acceso a este tenant',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 403);
            }
        }

        // Make tenant current
        $tenant->makeCurrent();

        // Log successful tenant context
        Log::info('[TenantApiMiddleware] Tenant context set', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
        ]);

        // Continue with request
        return $next($request);
    }
}
