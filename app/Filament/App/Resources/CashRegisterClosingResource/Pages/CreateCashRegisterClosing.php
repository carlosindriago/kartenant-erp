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
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCashRegisterClosing extends CreateRecord
{
    protected static string $resource = CashRegisterClosingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calcular saldo esperado si no está presente
        if (! isset($data['expected_balance'])) {
            $data['expected_balance'] = ($data['opening_balance'] ?? 0) + ($data['total_sales'] ?? 0);
        }

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        $difference = $this->getRecord()->difference;
        $hasDiscrepancy = abs($difference) > 0.01;

        return Notification::make()
            ->success()
            ->title('Cierre de Caja Registrado')
            ->body(
                $hasDiscrepancy
                    ? sprintf(
                        'Cierre registrado con %s de $%s',
                        $difference > 0 ? 'sobrante' : 'faltante',
                        number_format(abs($difference), 2)
                    )
                    : 'El cierre de caja se ha registrado exitosamente sin discrepancias.'
            )
            ->actions([
                \Filament\Notifications\Actions\Action::make('download_pdf')
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
                'closing_number' => $this->record->closing_number,
                'opening_number' => $this->record->opening->opening_number,
                'total_sales' => $this->record->total_sales,
                'difference' => $this->record->difference,
                'has_discrepancy' => $this->record->hasDiscrepancy(),
            ])
            ->log('Cierre de caja registrado');

        // Si hay discrepancia, registrar actividad adicional
        if ($this->record->hasDiscrepancy()) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($this->record)
                ->withProperties([
                    'difference' => $this->record->difference,
                    'expected_balance' => $this->record->expected_balance,
                    'closing_balance' => $this->record->closing_balance,
                ])
                ->log('Discrepancia detectada en cierre de caja');
        }
    }
}
