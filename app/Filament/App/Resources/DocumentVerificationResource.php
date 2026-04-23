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

use App\Filament\App\Resources\DocumentVerificationResource\Pages;
use App\Models\DocumentVerification;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentVerificationResource extends Resource
{
    protected static ?string $model = DocumentVerification::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Documentos Verificados';

    protected static ?string $modelLabel = 'Documento Verificado';

    protected static ?string $pluralModelLabel = 'Documentos Verificados';

    protected static ?string $navigationGroup = 'Seguridad';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Documento')
                    ->schema([
                        Forms\Components\TextInput::make('hash')
                            ->label('Hash de Verificación')
                            ->required()
                            ->maxLength(64)
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('document_type')
                            ->label('Tipo de Documento')
                            ->required()
                            ->maxLength(50)
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('generated_at')
                            ->label('Fecha de Generación')
                            ->required()
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Estado del Documento')
                    ->schema([
                        Forms\Components\Toggle::make('is_valid')
                            ->label('Documento Válido')
                            ->required()
                            ->helperText('Desactivar para invalidar manualmente este documento'),
                        Forms\Components\TextInput::make('verification_count')
                            ->label('Número de Verificaciones')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('last_verified_at')
                            ->label('Última Verificación')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Fecha de Expiración')
                            ->helperText('Dejar vacío para documentos sin expiración'),
                    ])->columns(2),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Información Adicional')
                            ->keyLabel('Campo')
                            ->valueLabel('Valor')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo de Documento')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sale_report' => 'success',
                        'inventory_report' => 'info',
                        'return_report' => 'warning',
                        'financial_report' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sale_report' => 'Reporte de Ventas',
                        'inventory_report' => 'Reporte de Inventario',
                        'return_report' => 'Reporte de Devoluciones',
                        'financial_report' => 'Reporte Financiero',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hash')
                    ->label('Hash')
                    ->limit(16)
                    ->tooltip(fn ($record) => $record->hash)
                    ->copyable()
                    ->copyMessage('Hash copiado')
                    ->searchable(),

                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Generado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->generated_at->diffForHumans()),

                Tables\Columns\TextColumn::make('verification_count')
                    ->label('Verificaciones')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('last_verified_at')
                    ->label('Última Verificación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Sin verificar'),

                Tables\Columns\IconColumn::make('is_valid')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Sin expiración')
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Tipo de Documento')
                    ->options([
                        'sale_report' => 'Reporte de Ventas',
                        'inventory_report' => 'Reporte de Inventario',
                        'return_report' => 'Reporte de Devoluciones',
                        'financial_report' => 'Reporte Financiero',
                    ])
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_valid')
                    ->label('Estado del Documento')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Válidos')
                    ->falseLabel('Solo Invalidados'),

                Tables\Filters\Filter::make('expired')
                    ->label('Documentos Expirados')
                    ->query(fn (Builder $query): Builder => $query->where('expires_at', '<', now()))
                    ->toggle(),

                Tables\Filters\Filter::make('generated_at')
                    ->label('Fecha de Generación')
                    ->form([
                        Forms\Components\DatePicker::make('generated_from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('generated_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['generated_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('generated_at', '>=', $date),
                            )
                            ->when(
                                $data['generated_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('generated_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->label('Ver Verificación')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('verify.hash', ['hash' => $record->hash]))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('invalidate')
                    ->label('Invalidar')
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
                    })
                    ->visible(fn ($record) => $record->is_valid),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('invalidate_bulk')
                        ->label('Invalidar Seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo de Invalidación')
                                ->required()
                                ->maxLength(500),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                if ($record->is_valid) {
                                    $record->invalidate($data['reason']);
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('generated_at', 'desc')
            ->poll('30s');
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
            'index' => Pages\ListDocumentVerifications::route('/'),
            'view' => Pages\ViewDocumentVerification::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->where('tenant_id', $tenant->id)
            ->latest('generated_at');
    }

    public static function canCreate(): bool
    {
        return false; // Los documentos se generan automáticamente
    }
}
