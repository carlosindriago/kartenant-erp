<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model as EloquentModel;

class AuditLogger
{
    /**
     * Log an audit event to the landlord activity log with standard properties.
     */
    public static function log(
        ?EloquentModel $subject,
        ?Authenticatable $causer,
        string $description,
        string $event,
        string $logName = 'auth',
        ?string $guard = null,
        array $properties = []
    ): void {
        $request = request();
        $guard = $guard ?? auth()->getDefaultDriver();

        $tenantId = null;
        try {
            if (function_exists('tenant')) {
                $t = tenant();
                if ($t) {
                    $tenantId = $t->id;
                }
            }
        } catch (\Throwable $e) {
            $tenantId = null;
        }

        $logger = activity($logName);

        if ($causer !== null) {
            $logger->causedBy($causer);
        }

        if ($subject instanceof EloquentModel) {
            $logger->performedOn($subject);
        }

        $logger->withProperties(array_merge([
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'url' => $request?->fullUrl(),
                'route' => $request?->path(),
                'method' => $request?->method(),
                'guard' => $guard,
                'tenant_id' => $tenantId,
            ], $properties))
            ->event($event)
            ->tap(function ($activity) use ($tenantId, $guard, $request) {
                // Persist also on dedicated columns
                $activity->tenant_id = $tenantId;
                $activity->guard = $guard;
                $activity->ip = $request?->ip();
                $activity->user_agent = $request?->userAgent();
                $activity->route = $request?->path();
                $activity->method = $request?->method();
            })
            ->log($description);
    }
}
