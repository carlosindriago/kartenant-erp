<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.quick-actions';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getActions(): array
    {
        return [
            [
                'title' => '💰 Abrir Caja',
                'description' => 'Iniciar operaciones del día',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'success',
                'url' => '/app/cash-registers/create',
                'badge' => null,
                'target' => '_self',
            ],
            [
                'title' => '🛒 Nueva Venta',
                'description' => 'Registrar venta rápida',
                'icon' => 'heroicon-o-shopping-cart',
                'color' => 'primary',
                'url' => '/pos',
                'badge' => 'Popular',
                'target' => '_blank',
            ],
            [
                'title' => '📦 Agregar Producto',
                'description' => 'Nuevo item al inventario',
                'icon' => 'heroicon-o-plus-circle',
                'color' => 'info',
                'url' => '/app/products/create',
                'badge' => null,
                'target' => '_self',
            ],
            [
                'title' => '👥 Nuevo Cliente',
                'description' => 'Registrar cliente',
                'icon' => 'heroicon-o-user-plus',
                'color' => 'warning',
                'url' => '/app/customers/create',
                'badge' => null,
                'target' => '_self',
            ],
            [
                'title' => '📊 Ver Reportes',
                'description' => 'Análisis y estadísticas',
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'secondary',
                'url' => '/app/reports',
                'badge' => 'Nuevo',
                'target' => '_self',
            ],
            [
                'title' => '⚙️ Configuración',
                'description' => 'Ajustes del sistema',
                'icon' => 'heroicon-o-cog-6-tooth',
                'color' => 'gray',
                'url' => '/app/settings',
                'badge' => null,
                'target' => '_self',
            ],
        ];
    }
}
