<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Resources\DocumentVerificationResource\Widgets;

use App\Models\DocumentVerification;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VerificationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $tenant = Filament::getTenant();

        $totalDocs = DocumentVerification::where('tenant_id', $tenant->id)->count();
        $validDocs = DocumentVerification::where('tenant_id', $tenant->id)->where('is_valid', true)->count();
        $totalVerifications = DocumentVerification::where('tenant_id', $tenant->id)->sum('verification_count');
        $recentDocs = DocumentVerification::where('tenant_id', $tenant->id)
            ->where('generated_at', '>=', now()->subDays(30))
            ->count();

        return [
            Stat::make('Total de Documentos', $totalDocs)
                ->description('Documentos generados')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Documentos Válidos', $validDocs)
                ->description(($totalDocs - $validDocs).' invalidados')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total de Verificaciones', $totalVerifications)
                ->description('Verificaciones realizadas')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color('info'),

            Stat::make('Últimos 30 días', $recentDocs)
                ->description('Documentos generados')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('warning'),
        ];
    }
}
