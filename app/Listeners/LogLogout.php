<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Logout;

class LogLogout
{
    public function handle(Logout $event): void
    {
        AuditLogger::log(
            subject: null,
            causer: $event->user,
            description: 'Cierre de sesión',
            event: 'logout',
            logName: 'auth',
            guard: $event->guard ?? null,
        );
    }
}
