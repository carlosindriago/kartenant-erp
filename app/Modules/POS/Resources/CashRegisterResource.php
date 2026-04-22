<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Resources;

use App\Modules\POS\Resources\CashRegisterResource\Pages;
use App\Modules\POS\Models\CashRegister;
use App\Modules\POS\Services\CashRegisterService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CashRegisterResource extends Resource
{
    protected static ?string $model = CashRegister::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Historial de Cajas';
    
    protected static ?string $modelLabel = 'Caja Registradora';
    
    protected static ?string $pluralModelLabel = 'Cajas Registradoras';
    
    protected static ?string $navigationGroup = 'Punto de Venta';
    
    protected static ?int $navigationSort = 3;
    
    // El modelo ya está en la base de datos del tenant, no necesita scope adicional
    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de Caja')
                    ->schema([
                        Forms\Components\TextInput::make('register_number')
                            ->label('Número de Registro')
                            ->disabled(),
                        
                        Forms\Components\Select::make('opened_by_user_id')
                            ->label('Abierto Por')
                            ->relationship('openedBy', 'name')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('opened_at')
                            ->label('Fecha/Hora Apertura')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('initial_amount')
                            ->label('Monto Inicial')
                            ->prefix('$')
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('opening_notes')
                            ->label('Notas de Apertura')
                            ->disabled()
                            ->rows(2),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Información de Cierre')
                    ->schema([
                        Forms\Components\Select::make('closed_by_user_id')
                            ->label('Cerrado Por')
                            ->relationship('closedBy', 'name')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('closed_at')
                            ->label('Fecha/Hora Cierre')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('expected_amount')
                            ->label('Monto Esperado')
                            ->prefix('$')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('actual_amount')
                            ->label('Monto Real')
                            ->prefix('$')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('difference')
                            ->label('Diferencia')
                            ->prefix('$')
                            ->disabled(),
                        
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'open' => 'Abierta',
                                'closed' => 'Cerrada',
                            ])
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('closing_notes')
                            ->label('Notas de Cierre')
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Información de Cierre Forzado')
                    ->schema([
                        Forms\Components\Toggle::make('forced_closure')
                            ->label('Cierre Forzado')
                            ->disabled()
                            ->inline(false),
                        
                        Forms\Components\Select::make('forced_by_user_id')
                            ->label('Forzado Por')
                            ->relationship('forcedBy', 'name')
                            ->disabled(),
                        
                        Forms\Components\Textarea::make('forced_reason')
                            ->label('Motivo del Cierre Forzado')
                            ->disabled()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record && $record->forced_closure),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('register_number')
                    ->label('Registro')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),
                
                Tables\Columns\TextColumn::make('openedBy.name')
                    ->label('Cajero')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('opened_at')
                    ->label('Apertura')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Cierre')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('En proceso'),
                
                Tables\Columns\TextColumn::make('initial_amount')
                    ->label('Inicial')
                    ->money('CLP', locale: 'es_CL')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('expected_amount')
                    ->label('Esperado')
                    ->money('CLP', locale: 'es_CL')
                    ->sortable()
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('actual_amount')
                    ->label('Real')
                    ->money('CLP', locale: 'es_CL')
                    ->sortable()
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('difference')
                    ->label('Diferencia')
                    ->money('CLP', locale: 'es_CL')
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state > 0 => 'warning',
                        $state < 0 => 'danger',
                        default => 'success',
                    })
                    ->weight('bold')
                    ->placeholder('N/A'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'success' => 'open',
                        'gray' => 'closed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => '🟢 Abierta',
                        'closed' => '🔒 Cerrada',
                        default => $state,
                    }),
                
                Tables\Columns\IconColumn::make('forced_closure')
                    ->label('Cierre Forzado')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (CashRegister $record) => 
                        $record->forced_closure 
                            ? "Cerrada forzadamente por: {$record->forcedBy?->name}" 
                            : 'Cierre normal'
                    )
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'open' => 'Abiertas',
                        'closed' => 'Cerradas',
                    ]),
                
                Tables\Filters\SelectFilter::make('opened_by_user_id')
                    ->label('Cajero')
                    ->relationship('openedBy', 'name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\Filter::make('opened_at')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('opened_at', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('opened_at', '<=', $date),
                            );
                    }),
                
                Tables\Filters\TernaryFilter::make('con_diferencias')
                    ->label('Con Diferencias')
                    ->queries(
                        true: fn (Builder $query) => $query->where('difference', '!=', 0),
                        false: fn (Builder $query) => $query->where('difference', '=', 0),
                        blank: fn (Builder $query) => $query,
                    )
                    ->placeholder('Todas')
                    ->trueLabel('Solo con diferencias')
                    ->falseLabel('Solo exactas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('force_close')
                    ->label('Forzar Cierre')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (CashRegister $record) => 
                        $record->isOpen() && 
                        auth('tenant')->user()->can('pos.force_close_registers')
                    )
                    ->requiresConfirmation()
                    ->modalHeading('⚠️ Forzar Cierre de Caja')
                    ->modalDescription(fn (CashRegister $record) => 
                        "Esta acción cerrará la caja {$record->register_number} del usuario {$record->openedBy->name}. " .
                        "El cajero recibirá una notificación con el motivo del cierre."
                    )
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->form([
                        Forms\Components\TextInput::make('actual_amount')
                            ->label('Monto Contado en Caja')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->helperText('Ingresa el monto real contado en la caja'),
                        
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo del Cierre Forzado')
                            ->required()
                            ->rows(3)
                            ->placeholder('Ej: Fin de turno sin cerrar, emergencia, error operativo, ausencia del cajero...')
                            ->helperText('Este motivo será enviado al cajero por notificación'),
                    ])
                    ->action(function (CashRegister $record, array $data): void {
                        try {
                            $service = app(\App\Modules\POS\Services\CashRegisterService::class);
                            
                            $closedRegister = $service->forceClosureByAdmin(
                                cashRegister: $record,
                                actualAmount: $data['actual_amount'],
                                reason: $data['reason'],
                                forcedByUserId: auth('tenant')->id()
                            );
                            
                            Notification::make()
                                ->success()
                                ->title('✅ Caja Cerrada Forzadamente')
                                ->body("La caja {$closedRegister->register_number} ha sido cerrada. Se ha notificado al cajero {$record->openedBy->name}.")
                                ->duration(8000)
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('❌ Error al Cerrar Caja')
                                ->body($e->getMessage())
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ])
            ->defaultSort('opened_at', 'desc');
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
            'index' => Pages\ListCashRegisters::route('/'),
            'view' => Pages\ViewCashRegister::route('/{record}'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Si no es supervisor, solo puede ver sus propias cajas
        $user = auth('tenant')->user();
        if ($user && !$user->can('pos.view_all_registers')) {
            $query->where('opened_by_user_id', $user->id);
        }
        
        return $query;
    }
    
    public static function canViewAny(): bool
    {
        // Permitir acceso si el usuario tiene permiso para acceder al POS
        return auth('tenant')->check();
    }
    
    public static function canCreate(): bool
    {
        // No se permite crear cajas desde aquí, solo desde OpenCashRegisterPage
        return false;
    }
    
    public static function canEdit($record): bool
    {
        // No se permite editar cajas
        return false;
    }
    
    public static function canDelete($record): bool
    {
        // Solo superadmin puede eliminar
        $user = auth('tenant')->user();
        return $user && $user->is_super_admin && $record->isClosed();
    }
}
