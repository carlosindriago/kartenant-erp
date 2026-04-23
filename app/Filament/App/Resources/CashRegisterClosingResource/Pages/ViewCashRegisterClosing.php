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
use Filament\Resources\Pages\ViewRecord;

class ViewCashRegisterClosing extends ViewRecord
{
    protected static string $resource = CashRegisterClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->approve())
                ->visible(fn () => $this->record->status === 'pending_review'),

            Actions\Action::make('download_pdf')
                ->label('PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->record->downloadPdf()),

            Actions\Action::make('verify')
                ->label('Verificar')
                ->icon('heroicon-o-shield-check')
                ->url(fn () => $this->record->getInternalVerificationRoute())
                ->openUrlInNewTab(),
        ];
    }
}
