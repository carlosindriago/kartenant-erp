<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Resources\CashRegisterClosingResource\Pages;

use App\Filament\App\Resources\CashRegisterClosingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashRegisterClosings extends ListRecords
{
    protected static string $resource = CashRegisterClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Cierre')
                ->icon('heroicon-o-plus'),
        ];
    }
}
