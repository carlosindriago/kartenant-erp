<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Resources\SecurityQuestionResource\Pages;

use App\Filament\Resources\SecurityQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSecurityQuestion extends CreateRecord
{
    protected static string $resource = SecurityQuestionResource::class;
}
