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
use Filament\Resources\Pages\ListRecords;

class ListSecurityQuestions extends ListRecords
{
    protected static string $resource = SecurityQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Pregunta')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTitle(): string
    {
        return 'Preguntas de Seguridad';
    }

    public function getSubheading(): string
    {
        return 'Gestiona las preguntas disponibles para la recuperación de contraseñas de usuarios.';
    }
}
