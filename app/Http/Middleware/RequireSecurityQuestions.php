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
use Filament\Facades\Filament;

class RequireSecurityQuestions
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Solo aplicar en el contexto de tenant (no admin)
        if (!$user || $request->is('admin/*')) {
            return $next($request);
        }

        // Si el usuario no tiene preguntas de seguridad configuradas
        if (!$user->hasSecurityQuestions()) {
            // Permitir acceso a la página de configuración de preguntas
            if ($request->is('app/setup-security-questions') || $request->is('app/logout')) {
                return $next($request);
            }

            // Redirigir a la configuración de preguntas de seguridad
            return redirect()->route('tenant.setup-security-questions', ['tenant' => Filament::getTenant()]);
        }

        return $next($request);
    }
}
