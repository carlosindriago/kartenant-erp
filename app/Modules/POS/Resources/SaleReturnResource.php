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

use App\Modules\POS\Models\SaleReturn;
use App\Modules\POS\Resources\SaleReturnResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SaleReturnResource extends Resource
{
    protected static ?string $model = SaleReturn::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static ?string $navigationLabel = 'Devoluciones';

    protected static ?string $modelLabel = 'Devolución';

    protected static ?string $pluralModelLabel = 'Devoluciones';

    protected static ?string $navigationGroup = 'Punto de Venta';

    protected static ?int $navigationSort = 3;

    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        // En database-per-tenant, no necesitamos filtrar por tenant_id
        return parent::getEloquentQuery()
            ->with(['originalSale', 'processedBy', 'items'])
            ->latest();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // View only
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('return_number')
                    ->label('Nº Nota')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('warning')
                    ->icon('heroicon-m-document-text'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('originalSale.invoice_number')
                    ->label('Venta')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-arrow-right')
                    ->url(fn (SaleReturn $record) => route('filament.app.resources.sales.view', [
                        'record' => $record->original_sale_id,
                        'tenant' => \Spatie\Multitenancy\Models\Tenant::current()->domain,
                    ])),

                Tables\Columns\BadgeColumn::make('return_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'full' => 'Completa',
                        'partial' => 'Parcial',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'full' => 'warning',
                        'partial' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Completada',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('refund_method')
                    ->label('Reembolso')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => '💵 Efectivo',
                        'card' => '💳 Tarjeta',
                        'transfer' => '🏦 Transferencia',
                        'credit_note' => '📄 N. Crédito',
                        default => ucfirst($state),
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD', true)
                    ->sortable()
                    ->weight('bold')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('processedBy.name')
                    ->label('Usuario')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'completed' => 'Completada',
                        'pending' => 'Pendiente',
                        'cancelled' => 'Cancelada',
                    ]),

                Tables\Filters\SelectFilter::make('return_type')
                    ->label('Tipo')
                    ->options([
                        'full' => 'Completa',
                        'partial' => 'Parcial',
                    ]),

                Tables\Filters\SelectFilter::make('refund_method')
                    ->label('Método de Reembolso')
                    ->options([
                        'cash' => 'Efectivo',
                        'card' => 'Tarjeta',
                        'transfer' => 'Transferencia',
                        'credit_note' => 'Nota de Crédito',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Detalle'),

                Tables\Actions\Action::make('download_pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn (SaleReturn $record) => route('tenant.pos.credit-note.pdf', [
                        'tenant' => \Spatie\Multitenancy\Models\Tenant::current()->domain,
                        'saleReturn' => $record->id,
                    ]))
                    ->openUrlInNewTab(),

                ExportAction::make()
                    ->label('Exportar')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar Seleccionados')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListSaleReturns::route('/'),
            'view' => Pages\ViewSaleReturn::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        // No permitir crear devoluciones directamente, solo desde las ventas
        return false;
    }
}
