<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Resources\DocumentVerificationResource\Pages;

use App\Filament\App\Resources\DocumentVerificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;

class ViewDocumentVerification extends ViewRecord
{
    protected static string $resource = DocumentVerificationResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('verify')
                ->label('Ver Página de Verificación')
                ->icon('heroicon-o-eye')
                ->url(fn ($record) => route('verify.hash', ['hash' => $record->hash]))
                ->openUrlInNewTab()
                ->color('primary'),
                
            Actions\Action::make('copy_hash')
                ->label('Copiar Hash')
                ->icon('heroicon-o-clipboard-document')
                ->action(function ($record) {
                    // El JS de Filament manejará el copy
                })
                ->color('gray')
                ->extraAttributes([
                    'x-on:click' => 'window.navigator.clipboard.writeText("' . $this->record->hash . '"); $tooltip("Hash copiado")',
                ]),
                
            Actions\Action::make('invalidate')
                ->label('Invalidar Documento')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Invalidar Documento')
                ->modalDescription('¿Estás seguro de que quieres invalidar este documento? Esta acción no se puede deshacer.')
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Motivo de Invalidación')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function ($record, array $data) {
                    $record->invalidate($data['reason']);
                    $this->refreshFormData(['is_valid']);
                })
                ->visible(fn ($record) => $record->is_valid),
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            DocumentVerificationResource\Widgets\VerificationLogsWidget::class,
        ];
    }
}
