<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services\Multitenancy;

use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class TenantTimezoneBootstrapper implements SwitchTenantTask
{
    public function makeCurrent(IsTenant $tenant): void
    {
        // Obtener la zona horaria del tenant
        $timezone = $tenant->timezone ?? config('app.timezone');
        
        // Configurar la zona horaria de la aplicación para este tenant
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        // Log removido: generaba demasiado ruido en logs (se ejecuta en cada request)
    }

    public function forgetCurrent(): void
    {
        // Restaurar la zona horaria por defecto (configurada en .env)
        $defaultTimezone = env('APP_TIMEZONE', 'UTC');
        config(['app.timezone' => $defaultTimezone]);
        date_default_timezone_set($defaultTimezone);

        // Log removido: generaba demasiado ruido en logs
    }
}
