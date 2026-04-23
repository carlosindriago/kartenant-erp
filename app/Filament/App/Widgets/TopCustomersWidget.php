<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\App\Widgets;

use App\Modules\POS\Models\Customer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

/**
 * TopCustomersWidget: Widget de Top Clientes del Mes
 *
 * Muestra los mejores clientes con insights:
 * - Total gastado en el mes
 * - Número de compras
 * - Ticket promedio
 * - Última compra (para identificar riesgos)
 * - CTAs para contactar/ver historial
 */
class TopCustomersWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'lg' => 4,  // Full width for better table readability
    ];

    protected static ?string $heading = '👥 Top 5 Clientes del Mes';

    protected static ?string $description = 'Tus mejores clientes y su actividad';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTopCustomersQuery())
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('#')
                    ->formatStateUsing(fn ($state) => "#{$state}")
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        1 => 'success',
                        2 => 'warning',
                        3 => 'info',
                        default => 'gray',
                    })
                    ->size('sm'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Cliente')
                    ->searchable()
                    ->weight('medium')
                    ->formatStateUsing(function ($record) {
                        $emoji = $record->total_month >= 5000 ? '💎' :
                                ($record->total_month >= 2000 ? '⭐' : '👤');

                        return "{$emoji} {$record->name}";
                    }),

                Tables\Columns\TextColumn::make('total_month')
                    ->label('Gastado (Mes)')
                    ->money('ARS')
                    ->weight('bold')
                    ->color('success')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('purchases_count')
                    ->label('Compras')
                    ->formatStateUsing(fn ($state) => number_format($state).' compras')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('avg_ticket')
                    ->label('Ticket Prom.')
                    ->formatStateUsing(function ($record) {
                        $avg = $record->purchases_count > 0
                            ? $record->total_month / $record->purchases_count
                            : 0;

                        return '$'.number_format($avg, 0);
                    })
                    ->alignRight(),

                Tables\Columns\TextColumn::make('last_purchase')
                    ->label('Última Compra')
                    ->dateTime('d/m/Y')
                    ->badge()
                    ->color(function ($record) {
                        if (! $record->last_purchase) {
                            return 'gray';
                        }

                        $daysAgo = now()->diffInDays($record->last_purchase);

                        if ($daysAgo <= 7) {
                            return 'success';
                        }
                        if ($daysAgo <= 15) {
                            return 'warning';
                        }

                        return 'danger';
                    })
                    ->icon(function ($record) {
                        if (! $record->last_purchase) {
                            return null;
                        }

                        $daysAgo = now()->diffInDays($record->last_purchase);

                        if ($daysAgo <= 7) {
                            return 'heroicon-o-check-circle';
                        }
                        if ($daysAgo <= 15) {
                            return 'heroicon-o-clock';
                        }

                        return 'heroicon-o-exclamation-triangle';
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable()
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(function ($record) {
                        if (! $record->phone) {
                            return '#';
                        }
                        $phone = preg_replace('/[^0-9]/', '', $record->phone);

                        return "https://wa.me/{$phone}";
                    })
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => ! empty($record->phone)),

                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => "/app/customers/{$record->id}")
                    ->color('info'),
            ])
            ->emptyStateHeading('Sin ventas este mes')
            ->emptyStateDescription('Aún no hay ventas registradas para este mes.')
            ->emptyStateIcon('heroicon-o-users')
            ->paginated(false);
    }

    protected function getTopCustomersQuery()
    {
        return Customer::query()
            ->select([
                'customers.*',
                DB::raw('COALESCE(SUM(sales.total), 0) as total_month'),
                DB::raw('COUNT(DISTINCT sales.id) as purchases_count'),
                DB::raw('MAX(sales.created_at) as last_purchase'),
                DB::raw('ROW_NUMBER() OVER (ORDER BY COALESCE(SUM(sales.total), 0) DESC) as position'),
            ])
            ->leftJoin('sales', function ($join) {
                $join->on('customers.id', '=', 'sales.customer_id')
                    ->where('sales.status', '=', 'completed')
                    ->whereYear('sales.created_at', '=', now()->year)
                    ->whereMonth('sales.created_at', '=', now()->month);
            })
            ->groupBy('customers.id')
            ->havingRaw('COALESCE(SUM(sales.total), 0) > 0')
            ->orderByRaw('COALESCE(SUM(sales.total), 0) DESC')
            ->limit(5);
    }
}
