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
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        AuditLogger::log(
            subject: null,
            causer: $event->user, // may be null
            description: 'Intento de inicio de sesión fallido',
            event: 'login_failed',
            logName: 'auth',
            guard: $event->guard ?? null,
        );
    }
}
