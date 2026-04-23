<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Helpers;

use Carbon\Carbon;
use Spatie\Multitenancy\Models\Tenant;

class TenantTimeHelper
{
    /**
     * Obtener la hora actual del tenant
     *
     * @return \Illuminate\Support\Carbon
     */
    public static function now()
    {
        return now();
    }

    /**
     * Obtener la zona horaria del tenant actual
     */
    public static function getTimezone(): string
    {
        $tenant = Tenant::current();

        if ($tenant && $tenant->timezone) {
            return $tenant->timezone;
        }

        return config('app.timezone');
    }

    /**
     * Convertir una fecha a la zona horaria del tenant
     *
     * @param  mixed  $date
     * @return \Illuminate\Support\Carbon
     */
    public static function toTenantTime($date)
    {
        if (! $date) {
            return null;
        }

        $carbonDate = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $carbonDate->setTimezone(self::getTimezone());
    }

    /**
     * Formatear una fecha en el formato del tenant
     *
     * @param  mixed  $date
     */
    public static function format($date, string $format = 'd/m/Y H:i:s'): ?string
    {
        if (! $date) {
            return null;
        }

        return self::toTenantTime($date)->format($format);
    }
}
