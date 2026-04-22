<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Resources\BackupLogResource\Pages;

use App\Filament\Resources\BackupLogResource;
use App\Services\TenantRestoreService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBackupLog extends ViewRecord
{
    protected static string $resource = BackupLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview Contenido')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->visible(fn ($record) => $record->status === 'success')
                ->action(function ($record) {
                    $restoreService = app(TenantRestoreService::class);

                    Notification::make()
                        ->title('Analizando Backup')
                        ->body('Por favor espera...')
                        ->info()
                        ->send();

                    $preview = $restoreService->previewBackup($record);

                    if ($preview['success']) {
                        $statsHtml = '<ul style="list-style: none; padding: 0;">';
                        foreach ($preview['statistics'] as $table => $count) {
                            $statsHtml .= "<li><strong>{$table}:</strong> {$count} registros</li>";
                        }
                        $statsHtml .= '</ul>';

                        Notification::make()
                            ->title('Contenido del Backup')
                            ->body(new \Illuminate\Support\HtmlString("
                                <p><strong>Fecha:</strong> {$preview['backup_date']->format('d/m/Y H:i')}</p>
                                <p><strong>Tamaño:</strong> {$preview['backup_size']}</p>
                                <hr>
                                {$statsHtml}
                            "))
                            ->success()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Error al Analizar')
                            ->body($preview['error'])
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('restore')
                ->label('Restaurar Base de Datos')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('⚠️ Restaurar Base de Datos')
                ->modalDescription(fn ($record) => "Vas a restaurar la base de datos '{$record->database_name}' al estado del {$record->created_at->format('d/m/Y H:i')}.\n\n🛡️ Se creará un backup de seguridad automático antes de restaurar.\n\n⚠️ SE PERDERÁN todos los cambios posteriores a esa fecha.")
                ->modalSubmitActionLabel('Sí, Restaurar')
                ->form([
                    Placeholder::make('warning')
                        ->content(fn ($record) => "⚠️ ADVERTENCIA CRÍTICA\n\nEsta acción reemplazará completamente la base de datos actual.\nTodos los datos desde {$record->created_at->format('d/m/Y H:i')} hasta ahora SE PERDERÁN.\n\nSe creará un backup de seguridad automáticamente para posible rollback.")
                        ->extraAttributes(['class' => 'text-danger-600 font-bold']),
                    TextInput::make('confirmation')
                        ->label('Escribe CONFIRMAR para continuar')
                        ->required()
                        ->rule('in:CONFIRMAR')
                        ->helperText('Debes escribir exactamente: CONFIRMAR (en mayúsculas)'),
                ])
                ->visible(fn ($record) =>
                    $record->status === 'success' &&
                    auth('superadmin')->user()?->is_super_admin
                )
                ->action(function ($record, $data) {
                    $restoreService = app(TenantRestoreService::class);

                    Notification::make()
                        ->title('Iniciando Restauración')
                        ->body('Creando backup de seguridad y restaurando... Esto puede tomar varios minutos.')
                        ->info()
                        ->persistent()
                        ->send();

                    $result = $restoreService->restoreFromBackup(
                        $record,
                        auth('superadmin')->id()
                    );

                    if ($result['success']) {
                        Notification::make()
                            ->title('✅ Restauración Exitosa')
                            ->body("Base de datos restaurada en {$result['duration']} segundos.\n\nBackup de seguridad ID: #{$result['safety_backup_id']}")
                            ->success()
                            ->persistent()
                            ->send();
                    } else {
                        $title = '❌ Restauración Fallida';
                        $body = $result['message'] ?? $result['error'];

                        if (isset($result['rollback']) && $result['rollback'] === 'success') {
                            $title = '⚠️ Restauración Fallida (Rollback OK)';
                            $body .= "\n\n✅ La base de datos original fue recuperada automáticamente.";
                        } elseif (isset($result['rollback']) && $result['rollback'] === 'failed') {
                            $title = '🚨 ERROR CRÍTICO';
                            $body .= "\n\n🚨 El rollback también falló. Contacta soporte URGENTE.";
                        }

                        Notification::make()
                            ->title($title)
                            ->body($body)
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Información General')
                    ->schema([
                        TextEntry::make('database_name')
                            ->label('Base de Datos'),
                        TextEntry::make('tenant.name')
                            ->label('Tenant')
                            ->default('Landlord (Sistema Central)'),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'success' => 'success',
                                'failed' => 'danger',
                                'running' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('backup_type')
                            ->label('Tipo')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'daily' => 'Automático',
                                'manual' => 'Manual',
                                default => $state,
                            }),
                    ])->columns(2),
                Section::make('Detalles del Archivo')
                    ->schema([
                        TextEntry::make('file_path')
                            ->label('Ruta del Archivo')
                            ->copyable(),
                        TextEntry::make('formatted_file_size')
                            ->label('Tamaño del Archivo'),
                    ])->columns(2),
                Section::make('Tiempos')
                    ->schema([
                        TextEntry::make('started_at')
                            ->label('Iniciado')
                            ->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('completed_at')
                            ->label('Completado')
                            ->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('duration')
                            ->label('Duración')
                            ->formatStateUsing(fn (?int $state): string =>
                                $state ? "{$state} segundos" : '-'
                            ),
                        TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d/m/Y H:i:s')
                            ->since(),
                    ])->columns(2),
                Section::make('Error')
                    ->schema([
                        TextEntry::make('error_message')
                            ->label('Mensaje de Error')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->status === 'failed'),
            ]);
    }
}
