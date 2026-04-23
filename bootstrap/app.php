<?php

use App\Http\Middleware\MakeSpatieTenantCurrent;
use App\Http\Middleware\PreventRegistrationAbuse;
use App\Http\Middleware\RequireInternalVerificationPermission;
use App\Http\Middleware\TenantApiMiddleware;
use App\Http\Middleware\TenantSecurityMiddleware;
use App\Services\ErrorMonitoringService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'require.internal.verification' => RequireInternalVerificationPermission::class,
            'tenant.api' => TenantApiMiddleware::class,
            'tenant.security' => TenantSecurityMiddleware::class,
            'prevent_registration_abuse' => PreventRegistrationAbuse::class,
        ]);

        // Add tenant context for ALL web requests (including Livewire)
        // The middleware safely skips if not on tenant subdomain
        $middleware->web(prepend: [
            MakeSpatieTenantCurrent::class,
        ]);

        // NOTE: MakeSpatieTenantCurrent is now in web middleware group
        // This ensures Livewire requests also have tenant context
        // The middleware safely skips admin routes and apex domain

        $middleware->trustProxies(at: '*');
        // UseLandlordPermissionRegistrar is ONLY applied in AdminPanelProvider middleware stack
        // to avoid interfering with tenant context
    })
    ->withSchedule(function (Schedule $schedule) {
        // Limpia registros antiguos según activitylog.delete_records_older_than_days (90 días)
        $schedule->command('activitylog:clean')->dailyAt('03:00');

        // Backups automáticos diarios de todas las bases de datos (landlord + tenants)
        $schedule->command('backup:tenants')->dailyAt('03:00')->withoutOverlapping();

        // Limpieza de backups antiguos (> 7 días)
        $schedule->command('backup:clean')->dailyAt('04:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Solo procesar errores si NO estamos en consola O si la app está completamente booteada
        $exceptions->reportable(function (Throwable $e) {
            // Verificar que la aplicación esté completamente inicializada
            if (! app()->isBooted() || ! app()->bound('db')) {
                // Si la app no está lista, no intentar reportar
                return;
            }

            // Solo reportar si NO estamos en consola (comandos artisan)
            if (app()->runningInConsole()) {
                return;
            }

            // Ahora sí, reportar el error
            try {
                $errorMonitoring = app(ErrorMonitoringService::class);
                $errorMonitoring->sendCriticalErrorToSlack($e);
            } catch (Exception $reportException) {
                // Si falla el reporte, no romper la aplicación
                logger()->error('Failed to report error', [
                    'original_error' => $e->getMessage(),
                    'reporting_error' => $reportException->getMessage(),
                ]);
            }
        });

        // Force JSON response for AJAX requests
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => get_class($e),
                ], 500);
            }
        });
    })->create();
