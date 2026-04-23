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

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\SubscriptionLimitService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class UpgradePlan extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static string $view = 'filament.app.pages.upgrade-plan';

    protected static ?string $navigationLabel = 'Actualizar Plan';

    protected static ?string $title = 'Actualizar Plan';

    public static function canAccess(): bool
    {
        return config('app.mode') === 'saas';
    }

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 100;

    protected static bool $shouldRegisterNavigation = false; // Don't show in menu by default

    public function getHeading(): string
    {
        return 'Actualizar Tu Plan';
    }

    public function getSubheading(): ?string
    {
        return 'Elige el plan perfecto para tu negocio';
    }

    public function getAvailablePlans(): array
    {
        return DB::connection('landlord')
            ->table('subscription_plans')
            ->where('is_active', true)
            ->where('is_visible', true)
            ->whereNull('deleted_at')
            ->orderBy('sort_order')
            ->orderBy('price_monthly')
            ->get()
            ->map(function ($plan) {
                // Decode JSON fields
                $plan->enabled_modules = json_decode($plan->enabled_modules, true) ?? [];
                $plan->features = json_decode($plan->features, true) ?? [];

                return $plan;
            })
            ->toArray();
    }

    public function getCurrentPlan()
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return null;
        }

        $limitService = new SubscriptionLimitService($tenant);

        return $limitService->getPlan();
    }

    public function getCurrentUsage(): array
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return [];
        }

        $limitService = new SubscriptionLimitService($tenant);

        return $limitService->getAllLimitsStatus();
    }

    public function selectPlan(int $planId, string $billingCycle): void
    {
        $plan = SubscriptionPlan::find($planId);

        if (! $plan) {
            Notification::make()
                ->title('Error')
                ->body('Plan no encontrado')
                ->danger()
                ->send();

            return;
        }

        if (! $plan->is_active) {
            Notification::make()
                ->title('Error')
                ->body('Este plan no está disponible actualmente')
                ->danger()
                ->send();

            return;
        }

        // TODO: Implement subscription creation/update logic
        // This will involve:
        // 1. Creating/updating TenantSubscription
        // 2. Creating Invoice
        // 3. Processing payment (Stripe, PayPal, etc.)
        // 4. Updating tenant's subscription_id

        Notification::make()
            ->title('Funcionalidad en Desarrollo')
            ->body("Has seleccionado: {$plan->name} ({$billingCycle}). La integración de pago está en desarrollo.")
            ->info()
            ->persistent()
            ->send();

        // For now, just show success message
        $tenant = Tenant::current();
        if ($tenant) {
            logger()->info('Plan selected', [
                'tenant_id' => $tenant->id,
                'plan_id' => $planId,
                'plan_name' => $plan->name,
                'billing_cycle' => $billingCycle,
            ]);
        }
    }

    public function contactSales(): void
    {
        Notification::make()
            ->title('Contacto de Ventas')
            ->body('Nuestro equipo de ventas se pondrá en contacto contigo pronto.')
            ->success()
            ->send();

        // TODO: Send notification to sales team
        $tenant = Tenant::current();
        if ($tenant) {
            logger()->info('Contact sales requested', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
            ]);
        }
    }
}
