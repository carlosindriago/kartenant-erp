<?php

/**
 * Kartenant - Ferretero Ágil
 *
 * Este archivo es parte de Kartenant.
 *
 * @copyright Copyright (c) 2025-2026 Kartenant
 * @license   GNU AGPLv3 <https://www.gnu.org/licenses/agpl-3.0.txt>
 */

namespace App\Modules\POS\Resources\CashRegisterResource\Pages;

use App\Modules\POS\Resources\CashRegisterResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Builder;

class ListCashRegisters extends ListRecords
{
    protected static string $resource = CashRegisterResource::class;

    protected function getHeaderActions(): array
    {
        $userId = auth('tenant')->id();

        return [
            Actions\Action::make('open_register')
                ->label('Abrir Mi Caja')
                ->icon('heroicon-o-lock-open')
                ->iconPosition(IconPosition::Before)
                ->color('success')
                ->url(\App\Filament\App\Pages\POS\OpenCashRegisterPage::getUrl())
                ->visible(fn () => $userId && ! \App\Modules\POS\Models\CashRegister::userHasOpenRegister($userId)),

            Actions\Action::make('close_register')
                ->label('Cerrar Mi Caja')
                ->icon('heroicon-o-lock-closed')
                ->iconPosition(IconPosition::Before)
                ->color('danger')
                ->url(\App\Filament\App\Pages\POS\CloseCashRegisterPage::getUrl())
                ->visible(fn () => $userId && \App\Modules\POS\Models\CashRegister::userHasOpenRegister($userId)),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todas')
                ->badge($this->getModel()::count()),

            'open' => Tab::make('Abiertas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'open'))
                ->badge($this->getModel()::where('status', 'open')->count())
                ->badgeColor('success'),

            'closed' => Tab::make('Cerradas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'closed'))
                ->badge($this->getModel()::where('status', 'closed')->count())
                ->badgeColor('gray'),

            'today' => Tab::make('Hoy')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('opened_at', today()))
                ->badge($this->getModel()::whereDate('opened_at', today())->count())
                ->badgeColor('primary'),

            'with_differences' => Tab::make('Con Diferencias')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'closed')
                    ->where('difference', '!=', 0)
                )
                ->badge($this->getModel()::where('status', 'closed')
                    ->where('difference', '!=', 0)
                    ->count())
                ->badgeColor('warning'),
        ];
    }
}
