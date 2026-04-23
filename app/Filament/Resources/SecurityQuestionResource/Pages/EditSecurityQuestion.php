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
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSecurityQuestion extends EditRecord
{
    protected static string $resource = SecurityQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->userSecurityAnswers()->exists()) {
                        Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Esta pregunta está siendo utilizada por usuarios. Desactívala en su lugar.')
                            ->danger()
                            ->send();
                        $action->cancel();
                    }
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Editar Pregunta de Seguridad';
    }
}
