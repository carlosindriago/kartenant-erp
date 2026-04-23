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

class CheckVerificationAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->getTenantFromRequest($request);

        // Si no hay tenant o la verificación está deshabilitada
        if (! $tenant || ! $tenant->verification_enabled) {
            abort(403, 'La verificación de documentos no está disponible.');
        }

        // Acceso público: permitir a todos
        if ($tenant->verification_access_type === 'public') {
            return $next($request);
        }

        // Acceso privado o por roles: requiere autenticación
        if (! auth()->check()) {
            return redirect()->route('filament.app.auth.login', ['tenant' => $tenant->domain])
                ->with('error', 'Debes iniciar sesión para verificar documentos.');
        }

        // Acceso privado: solo usuarios autenticados del tenant
        if ($tenant->verification_access_type === 'private') {
            // Verificar que el usuario pertenezca al tenant
            if (! $tenant->users()->where('users.id', auth()->id())->exists()) {
                abort(403, 'No tienes permiso para acceder a esta página.');
            }

            return $next($request);
        }

        // Acceso por roles: verificar roles permitidos
        if ($tenant->verification_access_type === 'role_based') {
            $user = auth()->user();

            // Verificar que el usuario pertenezca al tenant
            if (! $tenant->users()->where('users.id', $user->id)->exists()) {
                abort(403, 'No tienes permiso para acceder a esta página.');
            }

            // Verificar que el usuario tenga alguno de los roles permitidos
            $allowedRoles = $tenant->verification_allowed_roles ?? [];

            if (empty($allowedRoles)) {
                abort(403, 'No se han configurado roles permitidos para la verificación.');
            }

            // Usar el contexto del tenant para verificar roles
            $tenant->execute(function () use ($user, $allowedRoles) {
                $hasRole = $user->hasAnyRole($allowedRoles);

                if (! $hasRole) {
                    abort(403, 'No tienes el rol necesario para verificar documentos.');
                }
            });

            return $next($request);
        }

        // Tipo de acceso no reconocido
        abort(403, 'Configuración de acceso no válida.');
    }

    /**
     * Obtener el tenant desde la request
     */
    protected function getTenantFromRequest(Request $request): ?Tenant
    {
        // Intentar obtener el tenant actual
        $currentTenant = app()->has('currentTenant') ? app('currentTenant') : null;

        if ($currentTenant) {
            return $currentTenant;
        }

        // Si no hay tenant actual, intentar obtenerlo del dominio
        $domain = $request->getHost();

        return Tenant::where('domain', $domain)
            ->orWhere('domain', $domain.'.'.config('app.domain'))
            ->first();
    }
}
