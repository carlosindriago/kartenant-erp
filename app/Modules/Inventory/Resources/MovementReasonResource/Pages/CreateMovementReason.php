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
use Filament\Resources\Pages\CreateRecord;

class CreateMovementReason extends CreateRecord
{
    protected static string $resource = MovementReasonResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
