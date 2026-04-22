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
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Notifications\Notification;

class EnforcePlanLimits
{
    /**
     * Handle an incoming request.
     *
     * Verifica los límites del plan cuando se intenta crear recursos.
     */
    public function handle(Request $request, Closure $next, string $limitType = null): Response
    {
        // Skip for superadmin panel
        if ($request->is('admin/*')) {
            return $next($request);
        }

        // Get current tenant
        $tenant = Tenant::current();

        // Skip if not in tenant context
        if (!$tenant) {
            return $next($request);
        }

        // Skip for read operations (GET)
        if ($request->isMethod('get') && !$request->is('*/create')) {
            return $next($request);
        }

        $limitService = new SubscriptionLimitService($tenant);

        // Auto-detect limit type based on URL if not provided
        if (!$limitType) {
            $limitType = $this->detectLimitType($request);
        }

        // Check specific limit
        $canProceed = true;
        $limitStatus = null;

        switch ($limitType) {
            case 'users':
                $limitStatus = $limitService->canAddUser();
                $canProceed = $limitStatus['allowed'];
                break;

            case 'products':
                $limitStatus = $limitService->canAddProduct();
                $canProceed = $limitStatus['allowed'];
                break;

            case 'sales':
                $limitStatus = $limitService->canAddSale();
                $canProceed = $limitStatus['allowed'];
                break;
        }

        // If limit reached, show notification and redirect
        if (!$canProceed && $limitStatus) {
            Notification::make()
                ->title('Límite Alcanzado')
                ->body($limitStatus['reason'] ?? 'Has alcanzado el límite de tu plan actual.')
                ->warning()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('upgrade')
                        ->label('Actualizar Plan')
                        ->url(route('filament.app.pages.upgrade-plan', ['tenant' => $tenant]))
                        ->button()
                        ->markAsRead(),
                ])
                ->send();

            // Redirect back with error
            return back()->with('error', $limitStatus['reason']);
        }

        // Show warning if close to limit (>80%)
        if ($limitStatus && isset($limitStatus['percentage']) && $limitStatus['percentage'] >= 80 && $limitStatus['percentage'] < 100) {
            Notification::make()
                ->title('Acercándote al Límite')
                ->body("Has usado {$limitStatus['current']} de {$limitStatus['limit']} ({$limitStatus['percentage']}%). Considera actualizar tu plan.")
                ->warning()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('upgrade')
                        ->label('Ver Planes')
                        ->url(route('filament.app.pages.upgrade-plan', ['tenant' => $tenant]))
                        ->markAsRead(),
                ])
                ->send();
        }

        return $next($request);
    }

    /**
     * Auto-detect limit type based on request URL
     */
    protected function detectLimitType(Request $request): ?string
    {
        $path = $request->path();

        // Users
        if (str_contains($path, '/users') || str_contains($path, '/team')) {
            return 'users';
        }

        // Products
        if (str_contains($path, '/products') || str_contains($path, '/inventory')) {
            return 'products';
        }

        // Sales
        if (str_contains($path, '/sales') || str_contains($path, '/pos')) {
            return 'sales';
        }

        return null;
    }
}
