<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Reporting\Resources;

use Filament\Resources\Resource;
use App\Modules\Reporting\Resources\ReportCenterResource\Pages;

class ReportCenterResource extends Resource
{
    protected static ?string $model = null; // No model needed for reports

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Reportes Avanzados';
    protected static ?string $modelLabel = 'Reporte';
    protected static ?string $pluralModelLabel = 'Reportes Avanzados';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?int $navigationSort = 1;

    /**
     * Check if user can access reports
     */
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('inventory.view_reports');
    }

    /**
     * Get pages for this resource
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ReportCenter::route('/'),
        ];
    }

    /**
     * Get navigation badge (optional)
     */
    public static function getNavigationBadge(): ?string
    {
        return null; // Can add notification count later
    }

    /**
     * Get navigation badge color
     */
    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
