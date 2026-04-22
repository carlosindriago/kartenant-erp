<?php

/**
 * Kartenant - Ferretero Ágil
 * 
 * Este archivo es parte de Kartenant.
 * 
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Filament\Resources\SubscriptionPlanResource\Pages;

use App\Filament\Resources\SubscriptionPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;

class ListSubscriptionPlans extends ListRecords
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Nuevo Plan')
                ->icon('heroicon-o-plus-circle')
                ->modalWidth(MaxWidth::FiveExtraLarge),

            Actions\Action::make('view_archived')
                ->label('Ver Planes Archivados')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->tooltip('Ver planes inactivos y archivados')
                ->url(fn (): string => route('filament.admin.resources.subscription-plans.archived'))
                ->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return 'Planes de Suscripción';
    }

    public function getHeading(): string
    {
        return 'Planes de Suscripción';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Future: SubscriptionPlansStatsWidget
        ];
    }
}
