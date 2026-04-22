<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Resources\DocumentVerificationResource\Pages;

use App\Filament\App\Resources\DocumentVerificationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;

class ListDocumentVerifications extends ListRecords
{
    protected static string $resource = DocumentVerificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions - documentos se generan automáticamente
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            DocumentVerificationResource\Widgets\VerificationStatsWidget::class,
        ];
    }
}
