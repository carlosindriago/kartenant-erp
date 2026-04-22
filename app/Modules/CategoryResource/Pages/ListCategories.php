<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\CategoryResource\Pages;

use App\Modules\CategoryResource;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;
    
    /**
     * Recordatorio de Contexto: Asegura que el tenant esté activo antes de cargar registros
     */
    public function mount(): void
    {
        // Recordatorio de contexto - asegura que el tenant esté activo
        Filament::getTenant()?->makeCurrent();
        
        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
