<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Pages;

use App\Models\Tenant;
use App\Services\SubscriptionLimitService;
use Filament\Actions\Action;
use Filament\Pages\Page;

class SubscriptionStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.app.pages.subscription-status';

    protected static ?string $navigationLabel = 'Mi Suscripción';

    protected static ?string $title = 'Estado de Suscripción';

    public static function canAccess(): bool
    {
        return config('app.mode') === 'saas';
    }

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 99;

    protected static bool $shouldRegisterNavigation = false; // Only accessible via user menu

    public function getHeading(): string
    {
        return 'Estado de Tu Suscripción';
    }

    public function getSubheading(): ?string
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return 'Sin plan activo';
        }

        $limitService = new SubscriptionLimitService($tenant);
        $plan = $limitService->getPlan();

        return $plan ? "Plan Actual: {$plan->name}" : 'Sin plan activo';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upgrade')
                ->label('Actualizar Plan')
                ->icon('heroicon-o-arrow-up-circle')
                ->color('success')
                ->url(fn (): string => \App\Filament\App\Pages\UpgradePlan::getUrl(tenant: \Filament\Facades\Filament::getTenant())),

            Action::make('view_billing')
                ->label('Centro de Facturación')
                ->icon('heroicon-o-credit-card')
                ->color('primary')
                ->url(fn (): string => \App\Filament\App\Pages\BillingDashboard::getUrl(tenant: \Filament\Facades\Filament::getTenant())),

            Action::make('view_invoices')
                ->label('Ver Facturas')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url('#') // TODO: Link to invoices page
                ->visible(false), // Hide until invoices page is ready
        ];
    }

    public function getData(): array
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return [
                'limits' => [],
                'warnings' => [],
                'suggestions' => [],
                'needsUpgrade' => false,
            ];
        }

        $limitService = new SubscriptionLimitService($tenant);

        return [
            'limits' => $limitService->getAllLimitsStatus(),
            'warnings' => $limitService->hasWarnings(),
            'suggestions' => $limitService->getUpgradeSuggestions(),
            'needsUpgrade' => $limitService->needsUpgrade(),
        ];
    }
}
