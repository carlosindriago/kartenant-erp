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
use Symfony\Component\HttpFoundation\Response;

class AuthenticateTenantUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado con el guard tenant
        if (! auth('tenant')->check()) {
            // Intentar con guard web (fallback)
            if (! auth('web')->check()) {
                // Redirigir al login de Filament
                return redirect('/app/login');
            }

            // Si está autenticado en web pero no en tenant, sincronizar
            $user = auth('web')->user();
            auth('tenant')->login($user);

            \Log::info('🔄 Sesión sincronizada: web → tenant', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
        }

        return $next($request);
    }
}
