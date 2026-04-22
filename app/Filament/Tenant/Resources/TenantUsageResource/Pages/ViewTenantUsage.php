<?php

namespace App\Filament\Tenant\Resources\TenantUsageResource\Pages;

use App\Filament\Tenant\Resources\TenantUsageResource;
use App\Models\TenantUsage;
use App\Models\UsageAlert;
use App\Services\TenantUsageService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;

class ViewTenantUsage extends ViewRecord
{
    protected static string $resource = TenantUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Recalcular')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function (TenantUsage $record, TenantUsageService $usageService) {
                    $usageService->synchronizeCounters($record->tenant_id);
                    $record->calculatePercentages();
                    $this->notify('success', 'Uso recalculado exitosamente');
                }),

            Actions\Action::make('test_alert')
                ->label('Probar Alerta')
                ->icon('heroicon-o-bell')
                ->color('warning')
                ->visible(fn (TenantUsage $record) => $record->status !== 'normal')
                ->action(function (TenantUsage $record) {
                    // Send test alert
                    \App\Services\UsageAlertService::class::sendTestAlert($record->tenant_id, $record->status);
                    $this->notify('success', 'Alerta de prueba enviada');
                }),

            Actions\Action::make('view_billing')
                ->label('Actualizar Plan')
                ->icon('heroicon-o-credit-card')
                ->url(route('billing.index'))
                ->openUrlInNewTab()
                ->visible(fn (TenantUsage $record) => $record->upgrade_required_next_cycle),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            TenantUsageResource\Widgets\UsageBreakdown::class,
            TenantUsageResource\Widgets\RecentAlerts::class,
        ];
    }

    public function getTitle(): string
    {
        $record = $this->getRecord();
        return "Uso del Plan - {$record->getPeriodLabel()}";
    }

    protected function getTabTitles(): array
    {
        return [
            'overview' => 'Resumen',
            'alerts' => 'Alertas',
            'metrics' => 'Métricas Detalladas',
        ];
    }

    protected function getTabs(): array
    {
        $record = $this->getRecord();

        return [
            'overview' => \Filament\Infolists\Infolist::make()
                ->schema([
                    \Filament\Infolists\Components\Section::make('Resumen de Uso')
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make('status')
                                ->label('Estado General')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'normal' => 'success',
                                    'warning' => 'warning',
                                    'overdraft' => 'danger',
                                    'critical' => 'danger',
                                })
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'normal' => '✅ Normal',
                                    'warning' => '⚠️ Advertencia',
                                    'overdraft' => '🔴 Excedido',
                                    'critical' => '🚨 Crítico',
                                }),

                            \Filament\Infolists\Components\TextEntry::make('days_remaining')
                                ->label('Días Restantes')
                                ->getStateUsing(fn (TenantUsage $record) => $record->getDaysRemainingInPeriod() . ' días')
                                ->color(fn (TenantUsage $record) => $record->isNearPeriodEnd() ? 'danger' : 'primary'),

                            \Filament\Infolists\Components\IconEntry::make('upgrade_required_next_cycle')
                                ->label('Actualización Requerida')
                                ->boolean()
                                ->trueIcon('heroicon-o-exclamation-triangle')
                                ->falseIcon('heroicon-o-check-circle')
                                ->trueColor('danger')
                                ->falseColor('success'),
                        ])
                        ->columns(3),

                    \Filament\Infolists\Components\Section::make('Uso por Métrica')
                        ->schema([
                            \Filament\Infolists\Components\Grid::make(2)
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('sales_usage')
                                        ->label('Ventas')
                                        ->getStateUsing(function (TenantUsage $record) {
                                            $current = $record->sales_count;
                                            $limit = $record->max_sales_per_month;
                                            $percentage = $record->sales_percentage;
                                            $remaining = $record->getRemaining('sales');

                                            $limitText = $limit ? number_format($limit) : 'Ilimitado';
                                            $remainingText = $limit !== null ? number_format($remaining) : 'Ilimitado';

                                            return "**{$current} / {$limitText}** ({$percentage}%)\n\nRestantes: {$remainingText}";
                                        })
                                        ->markdown()
                                        ->color(fn (TenantUsage $record) =>
                                            $record->getZoneForMetric('sales') === 'critical' ? 'danger' :
                                            ($record->getZoneForMetric('sales') === 'overdraft' ? 'warning' :
                                            ($record->getZoneForMetric('sales') === 'warning' ? 'warning' : 'success'))
                                        ),

                                    \Filament\Infolists\Components\TextEntry::make('products_usage')
                                        ->label('Productos')
                                        ->getStateUsing(function (TenantUsage $record) {
                                            $current = $record->products_count;
                                            $limit = $record->max_products;
                                            $percentage = $record->products_percentage;
                                            $remaining = $record->getRemaining('products');

                                            $limitText = $limit ? number_format($limit) : 'Ilimitado';
                                            $remainingText = $limit !== null ? number_format($remaining) : 'Ilimitado';

                                            return "**{$current} / {$limitText}** ({$percentage}%)\n\nRestantes: {$remainingText}";
                                        })
                                        ->markdown()
                                        ->color(fn (TenantUsage $record) =>
                                            $record->getZoneForMetric('products') === 'critical' ? 'danger' :
                                            ($record->getZoneForMetric('products') === 'overdraft' ? 'warning' :
                                            ($record->getZoneForMetric('products') === 'warning' ? 'warning' : 'success'))
                                        ),

                                    \Filament\Infolists\Components\TextEntry::make('users_usage')
                                        ->label('Usuarios')
                                        ->getStateUsing(function (TenantUsage $record) {
                                            $current = $record->users_count;
                                            $limit = $record->max_users;
                                            $percentage = $record->users_percentage;
                                            $remaining = $record->getRemaining('users');

                                            $limitText = $limit ? number_format($limit) : 'Ilimitado';
                                            $remainingText = $limit !== null ? number_format($remaining) : 'Ilimitado';

                                            return "**{$current} / {$limitText}** ({$percentage}%)\n\nRestantes: {$remainingText}";
                                        })
                                        ->markdown()
                                        ->color(fn (TenantUsage $record) =>
                                            $record->getZoneForMetric('users') === 'critical' ? 'danger' :
                                            ($record->getZoneForMetric('users') === 'overdraft' ? 'warning' :
                                            ($record->getZoneForMetric('users') === 'warning' ? 'warning' : 'success'))
                                        ),

                                    \Filament\Infolists\Components\TextEntry::make('storage_usage')
                                        ->label('Almacenamiento')
                                        ->getStateUsing(function (TenantUsage $record) {
                                            $current = $record->storage_size_mb;
                                            $limit = $record->max_storage_mb;
                                            $percentage = $record->storage_percentage;
                                            $remaining = $record->getRemaining('storage');

                                            $limitText = $limit ? number_format($limit) . ' MB' : 'Ilimitado';
                                            $remainingText = $limit !== null ? number_format($remaining) . ' MB' : 'Ilimitado';

                                            return "**{$current} MB / {$limitText}** ({$percentage}%)\n\nRestantes: {$remainingText}";
                                        })
                                        ->markdown()
                                        ->color(fn (TenantUsage $record) =>
                                            $record->getZoneForMetric('storage') === 'critical' ? 'danger' :
                                            ($record->getZoneForMetric('storage') === 'overdraft' ? 'warning' :
                                            ($record->getZoneForMetric('storage') === 'warning' ? 'warning' : 'success'))
                                        ),
                                ]),
                        ]),

                    \Filament\Infolists\Components\Section::make('Información del Sistema')
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make('last_calculated_at')
                                ->label('Último Cálculo')
                                ->dateTime('d/m/Y H:i:s'),

                            \Filament\Infolists\Components\TextEntry::make('last_alert_sent_at')
                                ->label('Última Alerta Enviada')
                                ->dateTime('d/m/Y H:i:s')
                                ->placeholder('Ninguna'),
                        ])
                        ->columns(2),
                ]),

            'alerts' => \Filament\Tables\Table::make()
                ->query(
                    UsageAlert::where('tenant_usage_id', $record->id)
                        ->latest()
                )
                ->columns([
                    \Filament\Tables\Columns\TextColumn::make('created_at')
                        ->label('Fecha')
                        ->dateTime('d/m/Y H:i')
                        ->sortable(),
                    \Filament\Tables\Columns\TextColumn::make('alert_type')
                        ->label('Tipo')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'warning' => 'warning',
                            'overdraft' => 'danger',
                            'critical' => 'danger',
                        })
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'warning' => 'Advertencia',
                            'overdraft' => 'Excedido',
                            'critical' => 'Crítico',
                        }),
                    \Filament\Tables\Columns\TextColumn::make('metric_type')
                        ->label('Métrica')
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'sales' => 'Ventas',
                            'products' => 'Productos',
                            'users' => 'Usuarios',
                            'storage' => 'Almacenamiento',
                            'overall' => 'General',
                            default => ucfirst($state),
                        }),
                    \Filament\Tables\Columns\TextColumn::make('percentage')
                        ->label('Porcentaje')
                        ->suffix('%')
                        ->formatStateUsing(fn ($state) => number_format($state, 1))
                        ->color(fn ($record) => $record->percentage > 100 ? 'danger' : ($record->percentage >= 80 ? 'warning' : 'success')),
                    \Filament\Tables\Columns\TextColumn::make('delivery_status')
                        ->label('Estado de Envío')
                        ->badge()
                        ->getStateUsing(function ($record) {
                            $delivered = in_array('sent', $record->delivery_status ?? []);
                            $failed = in_array('failed', $record->delivery_status ?? []);

                            if ($delivered && !$failed) return 'Enviado';
                            if ($failed) return 'Parcialmente Fallado';
                            return 'Pendiente';
                        })
                        ->color(fn (string $state): string => match ($state) {
                            'Enviado' => 'success',
                            'Parcialmente Fallado' => 'warning',
                            'Pendiente' => 'gray',
                        }),
                ])
                ->paginated([10, 25, 50]),
        ];
    }
}