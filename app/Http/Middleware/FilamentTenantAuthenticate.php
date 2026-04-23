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

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FilamentTenantAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip authentication for tenant login routes (our custom flow)
        if ($request->is('login') || $request->is('two-factor*') || $request->is('logout')) {
            return $next($request);
        }

        // Check if user is authenticated with tenant guard
        if (! Auth::guard('tenant')->check()) {
            // Store intended URL for redirect after login
            session()->put('url.intended', $request->fullUrl());

            return redirect()->route('tenant.login')
                ->with('error', 'Por favor inicia sesión para continuar.');
        }

        // Verify tenant context is properly established
        $tenant = tenant();
        if (! $tenant || ! $tenant->is_active) {
            Auth::guard('tenant')->logout();

            return redirect()->route('tenant.login')
                ->with('error', 'Tu cuenta ha sido desactivada. Contacta al soporte.');
        }

        return $next($request);
    }
}
