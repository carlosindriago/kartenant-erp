<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantMigrationStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::current();

        if ($tenant && $tenant->migration_status === 'migrating') {
            abort(503, 'Estamos actualizando tu tienda con nuevas mejoras. Por favor, recarga la página en unos minutos.');
        }

        return $next($request);
    }
}
