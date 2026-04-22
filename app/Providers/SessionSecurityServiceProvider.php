<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

/**
 * SECURITY CRITICAL: Session Security Service Provider
 *
 * This service provider ensures that session encryption is always enabled
 * in production environments and prevents accidental misconfiguration.
 */
class SessionSecurityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // SECURITY CRITICAL: Force session encryption in production
        if ($this->app->environment('production')) {
            $sessionEncrypt = config('session.encrypt');

            if ($sessionEncrypt !== true) {
                Log::critical('SECURITY VIOLATION: Session encryption is disabled in production environment', [
                    'app_env' => config('app.env'),
                    'session_encrypt' => $sessionEncrypt,
                    'session_driver' => config('session.driver'),
                    'request_ip' => request()->ip() ?? 'cli',
                ]);

                // Force encryption to true for security
                config(['session.encrypt' => true]);

                Log::info('Session encryption force-enabled for production security');
            }
        }

        // DEVELOPMENT WARNING: Warn about session encryption in development
        if ($this->app->environment(['local', 'testing'])) {
            $sessionEncrypt = config('session.encrypt');

            if ($sessionEncrypt !== true) {
                Log::warning('Session encryption is disabled in development environment', [
                    'app_env' => config('app.env'),
                    'session_encrypt' => $sessionEncrypt,
                    'recommendation' => 'Set SESSION_ENCRYPT=true in .env for security testing',
                ]);
            }
        }
    }
}