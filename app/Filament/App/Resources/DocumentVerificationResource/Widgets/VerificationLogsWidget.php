<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Resources\DocumentVerificationResource\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\DocumentVerificationLog;
use Illuminate\Database\Eloquent\Model;

class VerificationLogsWidget extends BaseWidget
{
    public ?Model $record = null;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Historial de Verificaciones';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                DocumentVerificationLog::query()
                    ->where('verification_id', $this->record->id)
                    ->latest('verified_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Fecha de Verificación')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('Dirección IP')
                    ->searchable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Navegador')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->user_agent)
                    ->searchable(),
                    
                Tables\Columns\BadgeColumn::make('result')
                    ->label('Resultado')
                    ->colors([
                        'success' => 'valid',
                        'danger' => 'invalid',
                        'warning' => 'expired',
                        'gray' => 'not_found',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'valid' => 'Válido',
                        'invalid' => 'Invalidado',
                        'expired' => 'Expirado',
                        'not_found' => 'No Encontrado',
                        default => $state,
                    }),
            ])
            ->defaultSort('verified_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
