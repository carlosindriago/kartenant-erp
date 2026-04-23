<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Resources\CashRegisterOpeningResource\Pages;

use App\Filament\App\Resources\CashRegisterOpeningResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashRegisterOpenings extends ListRecords
{
    protected static string $resource = CashRegisterOpeningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Apertura')
                ->icon('heroicon-o-plus'),
        ];
    }
}
