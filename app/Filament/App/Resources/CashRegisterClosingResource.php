<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\CashRegisterClosingResource\Pages;
use App\Models\Tenancy\CashRegister\CashRegisterClosing;
use App\Models\Tenancy\CashRegister\CashRegisterOpening;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CashRegisterClosingResource extends Resource
{
    protected static ?string $model = CashRegisterClosing::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Cierres de Caja';

    protected static ?string $modelLabel = 'Cierre de Caja';

    protected static ?string $pluralModelLabel = 'Cierres de Caja';

    protected static ?string $navigationGroup = 'Caja';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Selección de Apertura')
                    ->schema([
                        Forms\Components\Select::make('opening_id')
                            ->label('Apertura de Caja')
                            ->options(
                                CashRegisterOpening::where('status', 'open')
                                    ->get()
                                    ->pluck('opening_number', 'id')
                            )
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $opening = CashRegisterOpening::find($state);
                                    if ($opening) {
                                        $set('opening_balance', $opening->opening_balance);
                                    }
                                }
                            })
                            ->disabled(fn ($record) => $record !== null),
                    ])
                    ->visible(fn ($record) => $record === null),

                Forms\Components\Section::make('Información de Cierre')
                    ->schema([
                        Forms\Components\TextInput::make('closing_number')
                            ->label('Número de Cierre')
                            ->disabled()
                            ->visible(fn ($record) => $record !== null),

                        Forms\Components\Select::make('closed_by')
                            ->label('Cerrado por')
                            ->relationship('closedBy', 'name')
                            ->default(fn () => auth()->id())
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\DateTimePicker::make('closed_at')
                            ->label('Fecha y Hora de Cierre')
                            ->default(now())
                            ->required()
                            ->disabled(fn ($record) => $record !== null),

                        Forms\Components\TextInput::make('opening_balance')
                            ->label('Saldo Inicial')
                            ->numeric()
                            ->prefix('$')
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Totales del Día')
                    ->schema([
                        Forms\Components\TextInput::make('total_sales')
                            ->label('Total Ventas')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('total_cash')
                            ->label('Total Efectivo')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('total_card')
                            ->label('Total Tarjeta')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('total_other')
                            ->label('Total Otros')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->reactive(),

                        Forms\Components\TextInput::make('total_transactions')
                            ->label('Total Transacciones')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('average_ticket')
                            ->label('Ticket Promedio')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Saldos')
                    ->schema([
                        Forms\Components\TextInput::make('expected_balance')
                            ->label('Saldo Esperado')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->reactive()
                            ->helperText('Saldo inicial + Total ventas'),

                        Forms\Components\TextInput::make('closing_balance')
                            ->label('Saldo Real Contado')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->reactive()
                            ->helperText('Dinero físico contado en caja'),

                        Forms\Components\Placeholder::make('difference_display')
                            ->label('Diferencia (Real - Esperado)')
                            ->content(function (callable $get) {
                                $closing = $get('closing_balance') ?? 0;
                                $expected = $get('expected_balance') ?? 0;
                                $diff = $closing - $expected;
                                $color = $diff >= 0 ? 'success' : 'danger';

                                return new HtmlString(
                                    '<span class="text-lg font-bold text-'.$color.'-600">$'.number_format($diff, 2).'</span>'
                                );
                            }),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Observaciones')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas del Cierre')
                            ->rows(3),

                        Forms\Components\Textarea::make('discrepancy_notes')
                            ->label('Notas sobre Discrepancias')
                            ->rows(3)
                            ->visible(fn (callable $get) => abs(($get('closing_balance') ?? 0) - ($get('expected_balance') ?? 0)) > 0.01),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\Select::make('pdf_format')
                            ->label('Formato de PDF')
                            ->options([
                                'thermal' => 'Térmico 80mm',
                                'a4' => 'A4 Estándar',
                            ])
                            ->default('thermal')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('closing_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('opening.opening_number')
                    ->label('Apertura')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('closedBy.name')
                    ->label('Cerrado por')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Fecha/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_sales')
                    ->label('Ventas')
                    ->money('ARS')
                    ->sortable(),

                Tables\Columns\TextColumn::make('difference')
                    ->label('Diferencia')
                    ->money('ARS')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pending_review',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_review' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending_review' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                    ]),

                Tables\Filters\Filter::make('closed_at')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'], fn ($query, $date) => $query->whereDate('closed_at', '>=', $date))
                            ->when($data['hasta'], fn ($query, $date) => $query->whereDate('closed_at', '<=', $date));
                    }),

                Tables\Filters\Filter::make('has_discrepancy')
                    ->label('Con Discrepancia')
                    ->query(fn ($query) => $query->whereRaw('ABS(difference) > 0.01')),
            ])
            ->actions([
                ExportAction::make()
                    ->label('Exportar')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success'),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (CashRegisterClosing $record) => $record->approve())
                    ->visible(fn (CashRegisterClosing $record) => $record->status === 'pending_review'),

                Tables\Actions\Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo del Rechazo')
                            ->required(),
                    ])
                    ->action(fn (CashRegisterClosing $record, array $data) => $record->reject($data['reason']))
                    ->visible(fn (CashRegisterClosing $record) => $record->status === 'pending_review'),

                Tables\Actions\Action::make('download_pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(fn (CashRegisterClosing $record) => $record->downloadPdf()),

                Tables\Actions\Action::make('verify')
                    ->label('Verificar')
                    ->icon('heroicon-o-shield-check')
                    ->url(fn (CashRegisterClosing $record) => $record->getInternalVerificationRoute())
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar Seleccionados')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('closed_at', 'desc');
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
            'index' => Pages\ListCashRegisterClosings::route('/'),
            'create' => Pages\CreateCashRegisterClosing::route('/create'),
            'view' => Pages\ViewCashRegisterClosing::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false; // Los cierres no se pueden editar una vez creados
    }
}
