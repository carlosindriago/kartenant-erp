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
use App\Services\SubscriptionLimitService;
use Closure;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionStatus
{
    /**
     * Handle an incoming request.
     *
     * Verifica que el tenant tenga una suscripción activa.
     * Si está expirada o suspendida, redirige a página de renovación.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for superadmin panel
        if ($request->is('admin/*')) {
            return $next($request);
        }

        // Get current tenant
        $tenant = Tenant::current();

        // Skip if not in tenant context
        if (! $tenant) {
            return $next($request);
        }

        $limitService = new SubscriptionLimitService($tenant);

        // Allow access to subscription management pages
        $allowedRoutes = [
            'filament.app.pages.subscription-status',
            'filament.app.pages.upgrade-plan',
            'filament.app.auth.logout',
        ];

        if (in_array($request->route()?->getName(), $allowedRoutes)) {
            return $next($request);
        }

        // Check if subscription is expired
        if ($limitService->isExpired()) {
            Notification::make()
                ->title('Suscripción Expirada')
                ->body('Tu suscripción ha expirado. Por favor renueva tu plan para continuar usando el sistema.')
                ->danger()
                ->persistent()
                ->send();

            // Redirect to subscription status page
            return redirect()->route('filament.app.pages.subscription-status', ['tenant' => $tenant]);
        }

        // Check if subscription is suspended
        if ($limitService->isSuspended()) {
            Notification::make()
                ->title('Cuenta Suspendida')
                ->body('Tu cuenta ha sido suspendida. Por favor contacta a soporte para más información.')
                ->danger()
                ->persistent()
                ->send();

            return redirect()->route('filament.app.pages.subscription-status', ['tenant' => $tenant]);
        }

        // Warn if trial is ending soon (last 3 days)
        if ($limitService->isOnTrial()) {
            $daysRemaining = $limitService->daysUntilTrialEnds();

            if ($daysRemaining !== null && abs($daysRemaining) <= 3) {
                Notification::make()
                    ->title('Período de Prueba por Vencer')
                    ->body('Tu período de prueba vence en '.abs($daysRemaining).' día(s). Actualiza tu método de pago para continuar sin interrupciones.')
                    ->warning()
                    ->actions([
                        Action::make('upgrade')
                            ->label('Actualizar Plan')
                            ->url(route('filament.app.pages.upgrade-plan', ['tenant' => $tenant]))
                            ->markAsRead(),
                    ])
                    ->send();
            }
        }

        // Warn if subscription is expiring soon (last 7 days)
        if ($limitService->hasActiveSubscription() && ! $limitService->isOnTrial()) {
            $daysUntilExpiration = $limitService->getAllLimitsStatus()['subscription']['days_until_expiration'];

            if ($daysUntilExpiration !== null && $daysUntilExpiration <= 7 && $daysUntilExpiration > 0) {
                Notification::make()
                    ->title('Renovación Próxima')
                    ->body("Tu suscripción vence en {$daysUntilExpiration} día(s).")
                    ->warning()
                    ->send();
            }
        }

        return $next($request);
    }
}
