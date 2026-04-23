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

/**
 * Middleware RequireInternalVerificationPermission
 *
 * Valida que el usuario tenga permisos para acceder a verificaciones internas
 */
class RequireInternalVerificationPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado
        if (! auth()->check()) {
            return redirect()->route('filament.app.auth.login')
                ->with('error', 'Debe iniciar sesión para verificar documentos internos.');
        }

        $user = auth()->user();

        // Verificar si el usuario tiene el permiso general de verificación interna
        if ($user->can('view_internal_verifications')) {
            return $next($request);
        }

        // Verificar si el usuario tiene algún rol autorizado
        if ($user->hasAnyRole(['admin', 'gerente', 'supervisor'])) {
            return $next($request);
        }

        // Si no tiene permisos, denegar acceso
        abort(403, 'No tiene permisos para verificar documentos internos.');
    }
}
