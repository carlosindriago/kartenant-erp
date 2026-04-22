<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Inventory\Resources\SupplierResource\Pages;

use App\Modules\Inventory\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSuppliers extends ManageRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Proveedor')
                ->icon('heroicon-o-plus')
                ->modalHeading('Registrar Proveedor')
                ->modalSubmitActionLabel('Registrar')
                ->successNotificationTitle('Proveedor registrado correctamente'),
        ];
    }
}
