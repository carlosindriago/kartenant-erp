<?php

namespace App\Filament\Tenant\Resources\TenantUsageResource\Widgets;

use App\Models\UsageAlert;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAlerts extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                UsageAlert::where('tenant_id', tenant()->id)
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn (UsageAlert $record): string => $record->created_at->diffForHumans()
                    ),

                Tables\Columns\TextColumn::make('alert_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'warning' => 'warning',
                        'overdraft' => 'danger',
                        'critical' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'warning' => '⚠️ Advertencia',
                        'overdraft' => '🔴 Excedido',
                        'critical' => '🚨 Crítico',
                    }),

                Tables\Columns\TextColumn::make('metric_type')
                    ->label('Métrica')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'sales' => 'Ventas',
                        'products' => 'Productos',
                        'users' => 'Usuarios',
                        'storage' => 'Almacenamiento',
                        'overall' => 'General',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('Porcentaje')
                    ->suffix('%')
                    ->formatStateUsing(fn ($state) => number_format($state, 1))
                    ->color(fn ($record) => $record->percentage > 100 ? 'danger' : ($record->percentage >= 80 ? 'warning' : 'success'))
                    ->description(fn (UsageAlert $record): string => "{$record->current_value} / {$record->limit_value}"
                    ),

                Tables\Columns\ViewColumn::make('delivery_status')
                    ->label('Envío')
                    ->view('filament.tables.columns.delivery-status')
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column, UsageAlert $record): ?string {
                        return strlen($record->message) > 50 ? $record->message : null;
                    })
                    ->wrap(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Detalles')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detalles de Alerta')
                    ->modalContent(function (UsageAlert $record) {
                        return view('filament.modals.alert-details', ['alert' => $record]);
                    })
                    ->modalWidth('2xl'),

                Tables\Actions\Action::make('resend')
                    ->label('Reenviar')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->action(function (UsageAlert $record) {
                        // Resend alert logic here
                        \App\Services\UsageAlertService::class::sendTestAlert($record->tenant_id, $record->alert_type);
                        $this->notify('success', 'Alerta reenviada exitosamente');
                    })
                    ->visible(fn (UsageAlert $record) => $record->isFailed()),
            ])
            ->paginated([5, 10, 25])
            ->emptyStateHeading('No hay alertas recientes')
            ->emptyStateDescription('Las alertas de uso aparecerán aquí cuando se alcancen los umbrales configurados.')
            ->emptyStateActions([
                Tables\Actions\Action::make('test_alert')
                    ->label('Probar Sistema de Alertas')
                    ->icon('heroicon-o-bell')
                    ->action(function () {
                        \App\Services\UsageAlertService::class::sendTestAlert(tenant()->id, 'warning');
                        $this->notify('success', 'Alerta de prueba enviada');
                    }),
            ])
            ->heading('Alertas Recientes')
            ->description('Historial de alertas de uso enviadas')
            ->headerActions([
                Tables\Actions\Action::make('test_alert')
                    ->label('Probar Alerta')
                    ->icon('heroicon-o-bell')
                    ->color('warning')
                    ->action(function () {
                        \App\Services\UsageAlertService::class::sendTestAlert(tenant()->id, 'warning');
                        $this->notify('success', 'Alerta de prueba enviada');
                    }),
            ]);
    }
}
