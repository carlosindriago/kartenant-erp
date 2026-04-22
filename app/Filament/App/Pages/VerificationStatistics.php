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
use App\Models\DocumentVerification;
use Filament\Facades\Filament;

class VerificationStatistics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    
    protected static ?string $navigationLabel = 'Estadísticas de Verificación';
    
    protected static ?string $title = 'Estadísticas de Verificación';
    
    protected static ?string $navigationGroup = 'Seguridad';
    
    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.app.pages.verification-statistics';
    
    public function getViewData(): array
    {
        $tenant = Filament::getTenant();
        
        return [
            'totalDocs' => DocumentVerification::where('tenant_id', $tenant->id)->count(),
            'validDocs' => DocumentVerification::where('tenant_id', $tenant->id)->where('is_valid', true)->count(),
            'totalVerifications' => DocumentVerification::where('tenant_id', $tenant->id)->sum('verification_count'),
            'recentDocs' => DocumentVerification::where('tenant_id', $tenant->id)
                ->where('generated_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }
}
