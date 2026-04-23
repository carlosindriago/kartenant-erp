<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\Inventory\Resources\MovementReasonResource\Pages;

use App\Modules\Inventory\Resources\MovementReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMovementReasons extends ListRecords
{
    protected static string $resource = MovementReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Motivo'),
        ];
    }
}
