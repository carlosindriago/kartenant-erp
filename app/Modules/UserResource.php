<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules;

use App\Modules\UserResource\Pages;
use App\Modules\UserResource\RelationManagers;
use App\Models\User;
use App\Models\UserStatusChange;
use App\Services\EmployeeEventService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Empleados';
    
    protected static ?string $modelLabel = 'Empleado';
    
    protected static ?string $pluralModelLabel = 'Empleados';
    
    protected static ?string $navigationGroup = 'Administración';
    
    protected static ?int $navigationSort = 2;
    
    // Disable Filament's tenant scoping since we manage this manually
    protected static bool $isScopedToTenant = false;
    
    public static function getEloquentQuery(): Builder
    {
        // Only show users that belong to the current tenant
        // Users are in landlord DB, but we filter by tenant relationship
        return parent::getEloquentQuery()
            ->whereHas('tenants', function ($query) {
                $query->where('tenants.id', \Filament\Facades\Filament::getTenant()->id);
            })
            ->where('is_super_admin', false); // Don't show superadmins
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Juan Pérez')
                            ->helperText('Nombre completo del empleado'),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('juan@empresa.com')
                            ->helperText('Este será el usuario para iniciar sesión'),
                    ]),
                
                Forms\Components\Section::make('Credenciales de Acceso')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->minLength(8)
                            ->maxLength(255)
                            ->placeholder('Mínimo 8 caracteres')
                            ->helperText('La contraseña debe tener al menos 8 caracteres'),
                        
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Confirmar Contraseña')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->dehydrated(false)
                            ->same('password')
                            ->placeholder('Repite la contraseña')
                            ->helperText('Debe coincidir con la contraseña'),
                        
                        Forms\Components\Toggle::make('must_change_password')
                            ->label('Forzar cambio de contraseña en el próximo inicio de sesión')
                            ->default(true)
                            ->helperText('El empleado deberá cambiar su contraseña al iniciar sesión por primera vez'),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($context) => $context === 'edit'),
                
                Forms\Components\Section::make('Roles y Permisos')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Rol del Empleado')
                            ->relationship('roles', 'name', function ($query) {
                                // Only show roles from current tenant (web guard)
                                return $query->where('guard_name', 'web');
                            })
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->placeholder('Selecciona uno o más roles')
                            ->helperText('Define qué puede hacer este empleado en el sistema')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-user'),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(',')
                    ->color('success')
                    ->placeholder('Sin rol'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->default(true)
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verificado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\IconColumn::make('must_change_password')
                    ->label('Debe Cambiar Contraseña')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado del Usuario')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Desactivados'),
                
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Por Rol')
                    ->relationship('roles', 'name', function ($query) {
                        return $query->where('guard_name', 'web');
                    })
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verificado')
                    ->nullable()
                    ->placeholder('Todos')
                    ->trueLabel('Verificados')
                    ->falseLabel('Sin verificar'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                // ===== ACCIONES DE GESTIÓN DE EMPLEADOS CON COMPROBANTES =====
                Tables\Actions\ActionGroup::make([
                    // Descargar comprobante de alta
                    Tables\Actions\Action::make('download_registration')
                        ->label('Comprobante de Alta')
                        ->icon('heroicon-o-document-check')
                        ->color('success')
                        ->visible(fn (User $record) => $record->is_active && auth()->user()->can('download_employee_certificates'))
                        ->requiresConfirmation(false)
                        ->action(function (User $record) {
                            $registrationEvent = $record->statusChanges()
                                ->where('action', 'registered')
                                ->latest()
                                ->first();
                            
                            if (!$registrationEvent) {
                                Notification::make()
                                    ->warning()
                                    ->title('No hay comprobante de alta')
                                    ->body('Este empleado no tiene un comprobante de alta registrado.')
                                    ->send();
                                return;
                            }
                            
                            $service = app(EmployeeEventService::class);
                            return $service->downloadEventPdf($registrationEvent);
                        }),
                    
                    // Desactivar empleado con comprobante
                    Tables\Actions\Action::make('deactivate')
                        ->label('Desactivar Empleado')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (User $record) => $record->is_active && auth()->user()->can('deactivate_employee'))
                        ->requiresConfirmation()
                        ->modalHeading('Desactivar Empleado')
                        ->modalDescription('Esta acción generará un comprobante verificable de desactivación.')
                        ->modalSubmitActionLabel('Desactivar y Generar Comprobante')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo de Desactivación')
                                ->required()
                                ->rows(3)
                                ->placeholder('Especifica el motivo de la desactivación...')
                                ->helperText('Este motivo aparecerá en el comprobante oficial'),
                            
                            Forms\Components\Textarea::make('notes')
                                ->label('Observaciones Adicionales (Opcional)')
                                ->rows(2)
                                ->placeholder('Notas internas...')
                                ->helperText('Información adicional para el expediente'),
                        ])
                        ->action(function (User $record, array $data) {
                            $service = app(EmployeeEventService::class);
                            $event = $service->registerEmployeeDeactivation(
                                $record,
                                auth()->user(),
                                $data['reason'],
                                $data['notes'] ?? null
                            );
                            
                            Notification::make()
                                ->success()
                                ->title('Empleado Desactivado')
                                ->body("Comprobante N° {$event->document_number} generado exitosamente.")
                                ->send();
                            
                            // Descargar automáticamente el comprobante
                            return $service->downloadEventPdf($event);
                        }),
                    
                    // Reactivar empleado con comprobante
                    Tables\Actions\Action::make('reactivate')
                        ->label('Reactivar Empleado')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (User $record) => !$record->is_active && auth()->user()->can('reactivate_employee'))
                        ->requiresConfirmation()
                        ->modalHeading('Reactivar Empleado')
                        ->modalDescription('Esta acción generará un comprobante verificable de reactivación.')
                        ->modalSubmitActionLabel('Reactivar y Generar Comprobante')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo de Reactivación')
                                ->required()
                                ->rows(3)
                                ->placeholder('Especifica el motivo de la reactivación...')
                                ->helperText('Este motivo aparecerá en el comprobante oficial'),
                            
                            Forms\Components\Textarea::make('notes')
                                ->label('Observaciones Adicionales (Opcional)')
                                ->rows(2)
                                ->placeholder('Notas internas...')
                                ->helperText('Información adicional para el expediente'),
                        ])
                        ->action(function (User $record, array $data) {
                            $service = app(EmployeeEventService::class);
                            $event = $service->registerEmployeeActivation(
                                $record,
                                auth()->user(),
                                $data['reason'],
                                $data['notes'] ?? null
                            );
                            
                            Notification::make()
                                ->success()
                                ->title('Empleado Reactivado')
                                ->body("Comprobante N° {$event->document_number} generado exitosamente.")
                                ->send();
                            
                            // Descargar automáticamente el comprobante
                            return $service->downloadEventPdf($event);
                        }),
                    
                    // Ver historial de eventos
                    Tables\Actions\Action::make('view_history')
                        ->label('Ver Historial')
                        ->icon('heroicon-o-clock')
                        ->color('info')
                        ->visible(fn () => auth()->user()->can('view_employee_history'))
                        ->url(fn (User $record): string => static::getUrl('view', ['record' => $record])),
                ])
                ->label('Gestión de Empleado')
                ->icon('heroicon-o-cog')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No hay empleados registrados')
            ->emptyStateDescription('Crea tu primer empleado para comenzar a gestionar tu equipo')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'summary' => Pages\EmployeeRegistrationSummary::route('/{record}/summary'),
        ];
    }
}
