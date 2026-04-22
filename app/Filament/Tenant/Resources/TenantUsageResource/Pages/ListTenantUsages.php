<?php

namespace App\Filament\Tenant\Resources\TenantUsageResource\Pages;

use App\Filament\Tenant\Resources\TenantUsageResource;
use App\Models\TenantUsage;
use App\Services\TenantUsageService;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTenantUsages extends ListRecords
{
    protected static string $resource = TenantUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh_current')
                ->label('Recalcular Uso Actual')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function (TenantUsageService $usageService) {
                    $tenantId = tenant()->id;
                    $usageService->synchronizeCounters($tenantId);
                    $this->notify('success', 'Uso recalculado exitosamente');
                }),

            Actions\Action::make('view_billing')
                ->label('Ver Planes de Facturación')
                ->icon('heroicon-o-credit-card')
                ->url(route('billing.index'))
                ->openUrlInNewTab(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TenantUsageResource\Widgets\UsageOverview::class,
            TenantUsageResource\Widgets\UsageChart::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos los Períodos'),
            'actual' => Tab::make('Período Actual')
                ->modifyQueryUsing(fn (Builder $query) => $query->currentPeriod()),
            'advertencia' => Tab::make('Con Advertencia')
                ->modifyQueryUsing(fn (Builder $query) => $query->warning()),
            'excedido' => Tab::make('Excedido')
                ->modifyQueryUsing(fn (Builder $query) => $query->overdraft()),
            'crítico' => Tab::make('Crítico')
                ->modifyQueryUsing(fn (Builder $query) => $query->critical()),
        ];
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No hay registros de uso';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Los registros de uso aparecerán aquí cuando haya actividad en tu cuenta. El uso se calcula automáticamente cada día.';
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Forzar Cálculo Inicial')
                ->icon('heroicon-o-arrow-path')
                ->action(function (TenantUsageService $usageService) {
                    $usageService->getCurrentUsage(tenant()->id, true);
                    $this->notify('success', 'Registro de uso inicial creado');
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Uso del Plan';
    }

    public function getSubtitle(): ?string
    {
        $currentUsage = TenantUsage::getCurrentUsage(tenant()->id);

        if (!$currentUsage) {
            return 'Sin registros de uso';
        }

        $statusConfig = [
            'normal' => ['text' => 'Uso Normal', 'color' => 'text-green-600'],
            'warning' => ['text' => 'Acercándose a límites', 'color' => 'text-yellow-600'],
            'overdraft' => ['text' => 'Límites Excedidos', 'color' => 'text-orange-600'],
            'critical' => ['text' => 'Límites Críticos', 'color' => 'text-red-600'],
        ];

        $config = $statusConfig[$currentUsage->status] ?? $statusConfig['normal'];

        return "<span class='{$config['color']} font-semibold'>{$config['text']}</span> - " .
               "{$currentUsage->getDaysRemainingInPeriod()} días restantes";
    }
}