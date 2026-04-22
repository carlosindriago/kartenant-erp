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

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplica en rutas del panel admin
        if (! $request->is('admin*')) {
            return $next($request);
        }

        $user = auth('superadmin')->user();
        if (! $user) {
            return $next($request);
        }

        // Permitir acceder a la página de cambio forzado, login y logout
        if ($request->is('admin/force-password-change*') || $request->is('admin/login*') || $request->is('admin/logout')) {
            return $next($request);
        }

        // Redirigir si el usuario debe cambiar su contraseña
        if (method_exists($user, 'getAttribute') && (bool) $user->getAttribute('must_change_password') === true) {
            return redirect('/admin/force-password-change');
        }

        return $next($request);
    }
}
