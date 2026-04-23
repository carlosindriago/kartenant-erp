<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\UserResource\Pages;

use App\Modules\UserResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil')
                ->color('warning'),

            Actions\Action::make('toggle_active')
                ->label(fn ($record) => $record->is_active ? 'Desactivar Usuario' : 'Activar Usuario')
                ->icon(fn ($record) => $record->is_active ? 'heroicon-o-user-minus' : 'heroicon-o-user-plus')
                ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                ->modalHeading(fn ($record) => $record->is_active ? '¿Desactivar usuario?' : '¿Activar usuario?')
                ->modalDescription(fn ($record) => $record->is_active
                    ? 'Se requiere justificación y código de seguridad para desactivar el usuario. Esta acción se puede revertir.'
                    : 'Se requiere justificación y código de seguridad para reactivar el usuario.')
                ->modalSubmitActionLabel(fn ($record) => $record->is_active ? 'Sí, desactivar' : 'Sí, activar')
                ->modalCancelActionLabel('Cancelar')
                ->form(function ($record) {
                    $currentUser = \Filament\Facades\Filament::auth()->user();

                    if ($record->is_active) {
                        // Formulario de DESACTIVACIÓN (con código de seguridad)
                        return [
                            \Filament\Forms\Components\Textarea::make('deactivation_reason')
                                ->label('Razón de la desactivación')
                                ->placeholder('Indique el motivo por el cual desactiva este usuario...')
                                ->required()
                                ->minLength(10)
                                ->maxLength(500)
                                ->rows(4)
                                ->helperText('Este registro quedará guardado para futuras referencias.')
                                ->validationMessages([
                                    'required' => 'Debe especificar una razón para desactivar el usuario.',
                                    'min' => 'La razón debe tener al menos 10 caracteres.',
                                ]),

                            \Filament\Forms\Components\Actions::make([
                                \Filament\Forms\Components\Actions\Action::make('send_deactivation_code')
                                    ->label('Solicitar Código de Seguridad')
                                    ->icon('heroicon-m-envelope')
                                    ->color('warning')
                                    ->action(function () use ($record, $currentUser) {
                                        // Generar código
                                        $code = $currentUser->generateSecurityCode();

                                        // Enviar email
                                        \Illuminate\Support\Facades\Mail::raw(
                                            "⚠️ CÓDIGO DE SEGURIDAD - DESACTIVACIÓN DE USUARIO\n\n".
                                            "Su código de seguridad para DESACTIVAR el usuario {$record->name} es:\n\n".
                                            "📌 CÓDIGO: {$code}\n\n".
                                            "Este código expirará en 10 minutos.\n\n".
                                            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
                                            "INFORMACIÓN DEL USUARIO A DESACTIVAR:\n".
                                            "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n".
                                            "Nombre: {$record->name}\n".
                                            "Email: {$record->email}\n".
                                            'Roles: '.$record->roles->pluck('name')->join(', ')."\n\n".
                                            'Si usted no solicitó este código, ignore este mensaje.',
                                            function ($message) use ($currentUser) {
                                                $message->to($currentUser->email)
                                                    ->subject('⚠️ Código de Seguridad - Desactivación de Usuario');
                                            }
                                        );

                                        \Filament\Notifications\Notification::make()
                                            ->title('Código enviado')
                                            ->warning()
                                            ->body("Se ha enviado un código de seguridad a {$currentUser->email}")
                                            ->send();
                                    }),
                            ]),

                            \Filament\Forms\Components\TextInput::make('deactivation_security_code')
                                ->label('Código de Seguridad')
                                ->placeholder('Ingrese el código de 6 dígitos')
                                ->required()
                                ->length(6)
                                ->numeric()
                                ->helperText('Ingrese el código enviado a su email para confirmar la desactivación.')
                                ->validationMessages([
                                    'required' => 'Debe ingresar el código de seguridad.',
                                    'length' => 'El código debe tener 6 dígitos.',
                                ]),
                        ];
                    } else {
                        // Formulario de REACTIVACIÓN (con código de seguridad)
                        return [
                            \Filament\Forms\Components\Textarea::make('reactivation_reason')
                                ->label('Razón de la reactivación')
                                ->placeholder('Indique el motivo por el cual reactiva este usuario...')
                                ->required()
                                ->minLength(10)
                                ->maxLength(500)
                                ->rows(4)
                                ->helperText('Este registro quedará guardado para futuras referencias.')
                                ->validationMessages([
                                    'required' => 'Debe especificar una razón para reactivar el usuario.',
                                    'min' => 'La razón debe tener al menos 10 caracteres.',
                                ]),

                            \Filament\Forms\Components\Actions::make([
                                \Filament\Forms\Components\Actions\Action::make('send_code')
                                    ->label('Solicitar Código de Seguridad')
                                    ->icon('heroicon-m-envelope')
                                    ->color('info')
                                    ->action(function () use ($record, $currentUser) {
                                        // Generar código
                                        $code = $currentUser->generateReactivationCode();

                                        // Enviar email
                                        \Illuminate\Support\Facades\Mail::raw(
                                            "Su código de seguridad para reactivar el usuario {$record->name} es: {$code}\n\n".
                                            "Este código expirará en 10 minutos.\n\n".
                                            "Usuario a reactivar: {$record->name} ({$record->email})\n".
                                            "Razón de desactivación previa: {$record->deactivation_reason}",
                                            function ($message) use ($currentUser) {
                                                $message->to($currentUser->email)
                                                    ->subject('Código de Seguridad - Reactivación de Usuario');
                                            }
                                        );

                                        \Filament\Notifications\Notification::make()
                                            ->title('Código enviado')
                                            ->success()
                                            ->body("Se ha enviado un código de seguridad a {$currentUser->email}")
                                            ->send();
                                    }),
                            ]),

                            \Filament\Forms\Components\TextInput::make('security_code')
                                ->label('Código de Seguridad')
                                ->placeholder('Ingrese el código de 6 dígitos')
                                ->required()
                                ->length(6)
                                ->numeric()
                                ->helperText('Ingrese el código enviado a su email.')
                                ->validationMessages([
                                    'required' => 'Debe ingresar el código de seguridad.',
                                    'length' => 'El código debe tener 6 dígitos.',
                                ]),
                        ];
                    }
                })
                ->successNotificationTitle(fn ($record) => $record->is_active ? 'Usuario desactivado correctamente' : 'Usuario activado correctamente')
                ->action(function ($record, array $data) {
                    $currentUser = \Filament\Facades\Filament::auth()->user();

                    if ($record->is_active) {
                        // DESACTIVAR (con validación de código)

                        // Validar código de seguridad
                        if (! isset($data['deactivation_security_code']) || ! $currentUser->verifySecurityCode($data['deactivation_security_code'])) {
                            \Filament\Notifications\Notification::make()
                                ->title('Código inválido o expirado')
                                ->danger()
                                ->body('El código de seguridad es incorrecto o ha expirado. Solicite un nuevo código.')
                                ->send();

                            throw new \Exception('Código de seguridad inválido');
                        }

                        // Desactivar el usuario
                        $record->update([
                            'is_active' => false,
                            'deactivation_reason' => $data['deactivation_reason'] ?? null,
                            'deactivated_at' => now(),
                            'deactivated_by' => $currentUser->id,
                        ]);

                        // Registrar en historial de cambios de estado
                        \App\Models\UserStatusChange::create([
                            'user_id' => $record->id,
                            'action' => 'deactivated',
                            'reason' => $data['deactivation_reason'] ?? null,
                            'changed_by' => $currentUser->id,
                            'changed_at' => now(),
                        ]);

                        // Limpiar código usado
                        $currentUser->update([
                            'reactivation_code' => null,
                            'reactivation_code_expires_at' => null,
                        ]);

                        // Log the action
                        activity()
                            ->causedBy($currentUser)
                            ->performedOn($record)
                            ->withProperties([
                                'action' => 'deactivated',
                                'reason' => $data['deactivation_reason'] ?? null,
                                'tenant' => \Filament\Facades\Filament::getTenant()->name,
                            ])
                            ->log('Usuario desactivado');
                    } else {
                        // REACTIVAR (con validación de código)

                        // Validar código de seguridad
                        if (! isset($data['security_code']) || ! $currentUser->verifyReactivationCode($data['security_code'])) {
                            \Filament\Notifications\Notification::make()
                                ->title('Código inválido o expirado')
                                ->danger()
                                ->body('El código de seguridad es incorrecto o ha expirado. Solicite un nuevo código.')
                                ->send();

                            throw new \Exception('Código de seguridad inválido');
                        }

                        // Reactivar el usuario
                        $record->update([
                            'is_active' => true,
                            'reactivation_reason' => $data['reactivation_reason'] ?? null,
                            'reactivated_at' => now(),
                            'reactivated_by' => $currentUser->id,
                        ]);

                        // Registrar en historial de cambios de estado
                        \App\Models\UserStatusChange::create([
                            'user_id' => $record->id,
                            'action' => 'activated',
                            'reason' => $data['reactivation_reason'] ?? null,
                            'changed_by' => $currentUser->id,
                            'changed_at' => now(),
                        ]);

                        // Limpiar código usado
                        $currentUser->update([
                            'reactivation_code' => null,
                            'reactivation_code_expires_at' => null,
                        ]);

                        // Log the action
                        activity()
                            ->causedBy($currentUser)
                            ->performedOn($record)
                            ->withProperties([
                                'action' => 'activated',
                                'reason' => $data['reactivation_reason'] ?? null,
                                'tenant' => \Filament\Facades\Filament::getTenant()->name,
                                'previous_deactivation' => [
                                    'reason' => $record->deactivation_reason,
                                    'date' => $record->deactivated_at?->toDateTimeString(),
                                    'deactivated_by' => $record->deactivatedBy?->name,
                                ],
                            ])
                            ->log('Usuario reactivado');
                    }
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información Personal')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Nombre Completo')
                                    ->icon('heroicon-m-user')
                                    ->weight(FontWeight::Bold)
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                                Infolists\Components\TextEntry::make('email')
                                    ->label('Correo Electrónico')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable()
                                    ->copyMessage('Email copiado')
                                    ->copyMessageDuration(1500),
                            ]),
                    ]),

                Infolists\Components\Section::make('Roles y Permisos')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Infolists\Components\TextEntry::make('roles.name')
                            ->label('Roles Asignados')
                            ->badge()
                            ->color('success')
                            ->placeholder('Sin roles asignados')
                            ->separator(','),

                        Infolists\Components\TextEntry::make('permissions.name')
                            ->label('Permisos Directos')
                            ->badge()
                            ->color('info')
                            ->placeholder('Sin permisos directos')
                            ->separator(',')
                            ->visible(fn ($record) => $record->permissions->isNotEmpty()),
                    ])
                    ->columns(1),

                Infolists\Components\Section::make('Estado de la Cuenta')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\IconEntry::make('is_active')
                                    ->label('Estado del Usuario')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger')
                                    ->getStateUsing(fn ($record) => $record->is_active ?? true),

                                Infolists\Components\IconEntry::make('email_verified_at')
                                    ->label('Email Verificado')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),

                                Infolists\Components\IconEntry::make('must_change_password')
                                    ->label('Debe Cambiar Contraseña')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-exclamation-triangle')
                                    ->falseIcon('heroicon-o-check-circle')
                                    ->trueColor('warning')
                                    ->falseColor('success'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Historial de Desactivaciones')
                    ->icon('heroicon-o-exclamation-circle')
                    ->description('Registro completo de todas las desactivaciones de este usuario')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('deactivationHistory')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('reason')
                                    ->label('Razón')
                                    ->icon('heroicon-m-document-text')
                                    ->columnSpanFull()
                                    ->color('danger')
                                    ->weight(FontWeight::Medium),

                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('changed_at')
                                            ->label('Fecha')
                                            ->icon('heroicon-m-calendar')
                                            ->dateTime('d/m/Y H:i')
                                            ->since(),

                                        Infolists\Components\TextEntry::make('changedBy.name')
                                            ->label('Desactivado por')
                                            ->icon('heroicon-m-user')
                                            ->default('Sistema'),

                                        Infolists\Components\TextEntry::make('changed_at')
                                            ->label('Hace')
                                            ->since()
                                            ->icon('heroicon-m-clock'),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->contained(false),
                    ])
                    ->visible(fn ($record) => $record->deactivationHistory()->exists())
                    ->collapsible()
                    ->collapsed(fn ($record) => $record->is_active),

                Infolists\Components\Section::make('Historial de Reactivaciones')
                    ->icon('heroicon-o-check-circle')
                    ->description('Registro completo de todas las reactivaciones de este usuario')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('activationHistory')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('reason')
                                    ->label('Razón')
                                    ->icon('heroicon-m-document-text')
                                    ->columnSpanFull()
                                    ->color('success')
                                    ->weight(FontWeight::Medium),

                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('changed_at')
                                            ->label('Fecha')
                                            ->icon('heroicon-m-calendar')
                                            ->dateTime('d/m/Y H:i')
                                            ->since(),

                                        Infolists\Components\TextEntry::make('changedBy.name')
                                            ->label('Reactivado por')
                                            ->icon('heroicon-m-user')
                                            ->default('Sistema'),

                                        Infolists\Components\TextEntry::make('changed_at')
                                            ->label('Hace')
                                            ->since()
                                            ->icon('heroicon-m-clock'),
                                    ]),
                            ])
                            ->columnSpanFull()
                            ->contained(false),
                    ])
                    ->visible(fn ($record) => $record->activationHistory()->exists())
                    ->collapsible()
                    ->collapsed(true),

                Infolists\Components\Section::make('Información de Auditoría')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Fecha de Registro')
                                    ->icon('heroicon-m-calendar')
                                    ->dateTime('d/m/Y H:i')
                                    ->since(),

                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Última Actualización')
                                    ->icon('heroicon-m-arrow-path')
                                    ->dateTime('d/m/Y H:i')
                                    ->since(),
                            ]),

                        Infolists\Components\TextEntry::make('email_verified_at')
                            ->label('Email Verificado el')
                            ->icon('heroicon-m-check-badge')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Email no verificado')
                            ->visible(fn ($record) => $record->email_verified_at !== null),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }
}
