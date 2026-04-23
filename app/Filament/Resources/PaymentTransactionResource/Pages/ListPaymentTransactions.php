<?php

namespace App\Filament\Resources\PaymentTransactionResource\Pages;

use App\Filament\Resources\PaymentTransactionResource;
use App\Models\PaymentTransaction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListPaymentTransactions extends ListRecords
{
    protected static string $resource = PaymentTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Transactions are created automatically, not manually
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todas'),
            'pending' => Tab::make('Pendientes')
                ->modifyQueryUsing(fn ($query) => $query->where('status', PaymentTransaction::STATUS_PENDING))
                ->badge(fn () => PaymentTransaction::on('landlord')->pending()->count())
                ->badgeColor('warning'),
            'approved' => Tab::make('Aprobadas')
                ->modifyQueryUsing(fn ($query) => $query->where('status', PaymentTransaction::STATUS_APPROVED)),
            'rejected' => Tab::make('Rechazadas')
                ->modifyQueryUsing(fn ($query) => $query->where('status', PaymentTransaction::STATUS_REJECTED)),
        ];
    }
}
