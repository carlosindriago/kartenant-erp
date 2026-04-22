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

use Filament\Pages\Page;

class POS extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static string $view = 'filament.app.pages.pos';
    
    protected static ?string $navigationLabel = 'Punto de Venta';
    
    protected static ?string $title = 'Punto de Venta';
    
    protected static ?string $navigationGroup = 'Punto de Venta';
    
    protected static ?int $navigationSort = 1;
    
    // Registrar en navegación
    protected static bool $shouldRegisterNavigation = true;
    
    // Obtener URL que abre en nueva pestaña al POS fullscreen
    public static function getUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null): string
    {
        $currentTenant = \Spatie\Multitenancy\Models\Tenant::current();
        
        if (!$currentTenant) {
            return '#';
        }
        
        // Extraer solo el subdominio (primera parte antes del punto)
        $subdomain = explode('.', $currentTenant->domain)[0];
        
        return route('tenant.pos', ['tenant' => $subdomain]);
    }
    
    // Abrir en nueva pestaña
    public static function shouldOpenUrlInNewTab(): bool
    {
        return true;
    }
    
    // Redirigir automáticamente al acceder a esta página
    public function mount(): void
    {
        $currentTenant = \Spatie\Multitenancy\Models\Tenant::current();
        
        if ($currentTenant) {
            // Extraer solo el subdominio (primera parte antes del punto)
            $subdomain = explode('.', $currentTenant->domain)[0];
            redirect()->route('tenant.pos', ['tenant' => $subdomain])->send();
        }
    }
}
