<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Auth\TwoFactorChallenge;
use App\Models\User;
use App\Http\Middleware\UseLandlordPermissionRegistrar;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
// ... otros imports
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Yebor974\Filament\RenewPassword\RenewPasswordPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->authGuard('superadmin')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Red,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\SubscriptionAlertsWidget::class,
                \App\Filament\Widgets\SubscriptionStatsWidget::class,
                \App\Filament\Widgets\SystemHealthWidget::class,
                \App\Filament\Widgets\BackupMonitorWidget::class,
                \App\Filament\Widgets\AnalyticsOverviewWidget::class,
                \App\Filament\Widgets\MostUsedFeaturesWidget::class,
                \App\Filament\Widgets\TrialVsPaidWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // Evita consultas a permisos/roles de tenants en el panel landlord
                UseLandlordPermissionRegistrar::class,
                // Removido: \App\Http\Middleware\ForcePasswordChange::class - ahora usa plugin
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugin(
                RenewPasswordPlugin::make()
                    ->forceRenewPassword(forceRenewColumn: 'force_renew_password') // Activar forzado
                    ->timestampColumn('last_password_change_at')
                    ->routeUri('cambiar-contrasena') // Ruta en español
            )
            // ... resto de la configuración
            ;
    }

    public function canAccessPanel(Model $user): bool
    {
        return $user instanceof User && (
            $user->is_super_admin === true ||
            $user->hasPermissionTo('admin.access', 'superadmin')
        );
    }
}