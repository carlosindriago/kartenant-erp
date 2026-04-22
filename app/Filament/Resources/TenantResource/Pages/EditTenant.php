<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ContentTabPosition;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->url(fn ($record) => static::getResource()::getUrl('view', ['record' => $record]))
                ->color('secondary')
                ->icon('heroicon-o-x-mark'),

            Actions\Action::make('save')
                ->label('Guardar Cambios')
                ->submit('save')
                ->color('primary')
                ->icon('heroicon-o-check'),
        ];
      }

    /**
     * Enable combined tabs mode to move form with buttons after RelationManagers
     */
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    /**
     * Position the form tab after RelationManagers so Save/Cancel buttons appear at the end
     */
    public function getContentTabPosition(): ?ContentTabPosition
    {
        return ContentTabPosition::After;
    }

    /**
     * Add icon for the combined form tab
     */
    public function getContentTabIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    /**
     * Add label for the combined form tab
     */
    public function getContentTabLabel(): string
    {
        return 'Información';
    }
}
