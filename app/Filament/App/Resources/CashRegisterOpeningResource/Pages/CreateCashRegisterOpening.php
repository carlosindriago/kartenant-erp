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
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCashRegisterOpening extends CreateRecord
{
    protected static string $resource = CashRegisterOpeningResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Apertura de Caja Creada')
            ->body('La apertura de caja se ha registrado exitosamente.')
            ->actions([
                Action::make('download_pdf')
                    ->button()
                    ->label('Descargar PDF')
                    ->url(fn () => route('tenant.internal-verification.pdf', $this->getRecord()->verification_hash))
                    ->openUrlInNewTab(),
            ]);
    }

    protected function afterCreate(): void
    {
        // Registrar actividad
        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->record)
            ->withProperties([
                'opening_number' => $this->record->opening_number,
                'opening_balance' => $this->record->opening_balance,
            ])
            ->log('Apertura de caja registrada');
    }
}
