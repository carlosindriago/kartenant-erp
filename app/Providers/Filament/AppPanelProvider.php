<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\BillingDashboard;
use App\Filament\App\Pages\POS;
use App\Filament\App\Pages\SubscriptionStatus;
use App\Filament\App\Pages\UpgradePlan;
use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnforcePlanLimits;
use App\Http\Middleware\FilamentTenantAuthenticate;
use App\Http\Middleware\MakeSpatieTenantCurrent;
use App\Http\Middleware\RequirePasswordChange;
use App\Models\Tenant;
use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            // Nota: en tenancy, usamos tenantDomain() para dom. multi-tenant; no establecer domain() aquí
            ->authGuard('tenant')
            ->loginUrl('/tenant/login')
            // Definimos la URL de inicio del panel explícitamente para no depender del nombre de ruta del dashboard
            ->homeUrl('/app')
            // --- ¡LA MAGIA MULTI-TENANT OCURRE AQUÍ! ---
            ->tenant(
                Tenant::class,
                slugAttribute: 'domain', // Le dice a Filament que el subdominio es la clave
                ownershipRelationship: 'users' // Le dice cómo encontrar a los usuarios del tenant
            )
            // Domain-based tenancy: move tenant param to domain and remove from path
            ->tenantDomain('{tenant}.kartenant.test')
            ->tenantRoutePrefix(null)
            ->tenantMenu(false) // Oculta el menú para cambiar de tenant (no lo necesitamos)
            ->viteTheme('resources/css/filament/app/theme.css')
            // ---------------------------------------------
            // Branding personalizado del tenant
            ->brandName(fn () => $this->getTenantBrandName())
            ->brandLogo(fn () => $this->getTenantBrandLogo())
            ->brandLogoHeight('3rem')
            ->favicon(fn () => $this->getTenantFavicon())
            // ---------------------------------------------
            ->colors([
                'primary' => Color::Amber,
            ])
            // Configuración del menú lateral
            ->sidebarCollapsibleOnDesktop() // Permite colapsar el sidebar en desktop
            ->sidebarWidth('16rem') // Ancho cuando está expandido
            ->collapsedSidebarWidth('4rem') // Ancho cuando está colapsado
            ->navigationGroups([
                NavigationGroup::make('Inventario')
                    ->collapsed(), // Colapsado por defecto
                NavigationGroup::make('Punto de Venta')
                    ->collapsed(),
                NavigationGroup::make('Caja')
                    ->collapsed(),
                NavigationGroup::make('Administración')
                    ->collapsed(),
                NavigationGroup::make('Configuración')
                    ->collapsed(),
                NavigationGroup::make('Seguridad')
                    ->collapsed(),
                NavigationGroup::make('Sistema')
                    ->collapsed(),
            ])
            ->discoverResources(in: app_path('Modules'), for: 'App\\Modules')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([
                POS::class,
                SubscriptionStatus::class,
                BillingDashboard::class,
                UpgradePlan::class,
            ])
            ->discoverWidgets(in: app_path('Modules'), for: 'App\\Modules')
            ->widgets([
                // TEMPORALMENTE DESHABILITADOS - Problema de permisos en widgets
                // Dashboard Premium - Widgets Principales
                // \App\Filament\App\Widgets\CriticalAlertsWidget::class, // Movido a notificaciones en topbar
                // \App\Filament\App\Widgets\RevenueStatsWidget::class,
                // \App\Filament\App\Widgets\RecommendedActionsWidget::class,
                // \App\Filament\App\Widgets\TopProductsWidget::class,
                // \App\Filament\App\Widgets\SalesTrendChartWidget::class,
                // \App\Filament\App\Widgets\StagnantProductsWidget::class,
                // \App\Filament\App\Widgets\TopCustomersWidget::class,
                // \App\Filament\App\Widgets\CashRegisterStatusWidget::class,
            ])
            ->userMenuItems(config('app.mode') === 'saas' ? [
                'subscription' => MenuItem::make()
                    ->label('Mi Suscripción')
                    ->icon('heroicon-o-credit-card')
                    ->url(fn () => SubscriptionStatus::getUrl(['tenant' => Filament::getTenant()]))
                    ->sort(10),
                'upgrade' => MenuItem::make()
                    ->label('Actualizar Plan')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->url(fn () => UpgradePlan::getUrl(['tenant' => Filament::getTenant()]))
                    ->sort(11),
            ] : [])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                // CRITICAL: MakeSpatieTenantCurrent MUST run before StartSession
                // to ensure tenant DB is configured before session loads
                MakeSpatieTenantCurrent::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                FilamentTenantAuthenticate::class,
                RequirePasswordChange::class,
                // Security questions feature disabled - table doesn't exist in tenant context
                // \App\Http\Middleware\RequireSecurityQuestions::class,

                // Subscription enforcement
                CheckSubscriptionStatus::class,
                EnforcePlanLimits::class,
            ])
            ->renderHook(
                'panels::sidebar.nav.end',
                fn () => view('components.bug-report-sidebar-button')
            )
            ->renderHook(
                'panels::user-menu.before',
                fn () => view('filament.critical-alerts-topbar')
            )
            ->renderHook(
                'panels::body.end',
                fn () => view('components.navigation-accordion')
            )
            ->renderHook(
                'panels::body.end',
                fn () => Blade::render('@livewire(\'bug-report-modal\')')
            )
            // FIX: Workaround para problema de navegación intermitente de Filament/Livewire
            ->renderHook(
                'panels::body.end',
                fn () => view('components.navigation-fix')
            );
    }

    /**
     * Get tenant brand name for display in navigation and login
     */
    protected function getTenantBrandName(): ?string
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return 'Kartenant';
        }

        // Si usa logo de imagen, no mostrar nombre
        if ($tenant->usesImageLogo()) {
            return null;
        }

        // Retornar el nombre personalizado o el nombre del tenant
        return $tenant->display_name ?? $tenant->name;
    }

    /**
     * Get tenant brand logo URL for display in navigation and login
     */
    protected function getTenantBrandLogo(): ?string
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return null;
        }

        // Si el tenant usa logo de imagen, retornar la URL
        if ($tenant->usesImageLogo() && $tenant->logo_url) {
            return $tenant->logo_url;
        }

        return null;
    }

    /**
     * Get tenant favicon
     */
    protected function getTenantFavicon(): ?string
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            return null;
        }

        // Si tiene logo, usar como favicon
        if ($tenant->usesImageLogo() && $tenant->logo_url) {
            return $tenant->logo_url;
        }

        return null;
    }
}
