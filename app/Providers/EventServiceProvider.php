<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Providers;

use App\Listeners\LogFailedLogin;
use App\Listeners\LogLogout;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogTenantIdentificationFailure;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Spatie\Multitenancy\Events\TenantNotFoundForRequestEvent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Login::class => [
            LogSuccessfulLogin::class,
        ],
        Logout::class => [
            LogLogout::class,
        ],
        Failed::class => [
            LogFailedLogin::class,
        ],
        TenantNotFoundForRequestEvent::class => [
            LogTenantIdentificationFailure::class,
        ],
    ];
}
